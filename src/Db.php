<?php

/**
 * PDOラッパークラス(MySQL版)
 * ※クエリログを記録する場合はLoggerクラスと併用すること
 *
 * Ver1.51 変更点:
 *   ・ベンチマーク対応
 *   ・Exceptionを追加
 *   ・クエリログにバインド後のクエリを出力するようにした
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2014, Noriyoshi Takahashi
 * @version   1.51 (2016.02.09 modified)
 */
class Db
{

    /** クエリログの有効/無効 */
    const ENABLE_QUERY_LOG = true;

    /** ベンチマークの有効/無効 */
    const ENABLE_BENCHMARK = true;

    /** スロークエリログの有効/無効 */
    const ENABLE_SLOW_QUERY_LOG = true;

    /** スロークエリ判定とする秒数 */
    const SLOW_QUERY_BORDER_SEC = 0.5;

    /** 実行したクエリのスタック */
    private static $query_stack = array();

    /** 最後に実行したクエリの文字列 */
    private static $last_query = '';

    /** 最後に実行したクエリの実行時間 */
    private static $last_query_execution_time = 0;

    /** クエリログを記録するか否か */
    protected static $enable_logging = false;
    protected static $query_log_prefix  = '';
    protected static $query_log_message = '';

    /** ベンチマークを有効／無効 */
    protected static $enable_benchmark = true;
    /** スロークエリと判断する秒数 */
    protected static $slow_query_sec = 0.5;
    /** プロファイリング用 */
    protected static $query_start_time;
    protected static $query_exec_time;
    protected static $query_start_memory;
    protected static $query_memory_usage;
    /** 接続情報 */
    protected static $hostname = null;
    protected static $dbname = null;
    protected static $username = null;
    protected static $password = null;
    protected static $connection = null;
    /** カラム情報 */
    protected static $describe;
    /** ログイン系カラム名 */
    const LOGIN_ID_COLUMN_NAME       = 'login_id';
    const LOGIN_PASSWORD_COLUMN_NAME = 'login_password';
    const LOGIN_HASH_COLUMN_NAME     = 'login_hash';
    /** 最終ログイン系カラム名 */
    const LAST_LOGIN_DATETIME_COLUMN_NAME   = 'last_login_at';
    const LAST_LOGIN_IP_COLUMN_NAME         = 'last_login_ip';
    const LAST_LOGIN_USER_AGENT_COLUMN_NAME = 'last_login_useragent';
    /** 有効フラグカラム名 */
    const ACTIVE_FLAG_COLUMN_NAME = 'is_active';
    /** 表示フラグカラム名 */
    const DISPLAY_FLAG_COLUMN_NAME = 'is_display';
    /** 削除フラグカラム名 */
    const DELETE_FLAG_COLUMN_NAME = 'is_delete';
    /** 作成日時カラム名 */
    const CREATED_AT_COLUMN_NAME = 'created_at';
    /** 更新日時カラム名 */
    const UPDATED_AT_COLUMN_NAME = 'updated_at';
    /** 作成者カラム名 */
    const CREATED_BY_COLUMN_NAME = 'created_by';
    /** 更新者カラム名 */
    const UPDATED_BY_COLUMN_NAME = 'updated_by';
    /** バイナリデータカラム名 */
    const BINARY_DATA_COLUMN_NAME = 'binary_data';

    /** 共通パラメータ定数 */
    const FLAG_OFF        = '0';
    const FLAG_ON         = '1';
    const DELETE_FLAG_OFF = '0';
    const DELETE_FLAG_ON  = '1';
    const NOW             = 'NOW()';
    /** メッセージフォーマット */
    const MESSAGE_FAILED_INSERT    = 'データの挿入に失敗しました';
    const MESSAGE_FAILED_UPDATE    = 'データの更新に失敗しました';
    const MESSAGE_FAILED_COMMIT    = 'コミットに失敗しました';
    const MESSAGE_FAILED_ROLLBACK  = 'ロールバックに失敗しました';
    const MESSAGE_NOT_EXISTS_DATA  = 'データが存在しません';
    const MESSAGE_UNEXPECTED_ERROR = '予期せぬエラーが発生しました';
    /** Exceptionコード */
    const EXCEPTION_CODE_FAILED_CONNECTION        = 80001;
    const EXCEPTION_CODE_FAILED_CLOSE             = 80002;
    const EXCEPTION_CODE_FAILED_EXECUTE_QUERY     = 80003;
    const EXCEPTION_CODE_FAILED_BEGIN_TRANSACTION = 80004;
    const EXCEPTION_CODE_FAILED_COMMIT            = 80005;
    const EXCEPTION_CODE_FAILED_ROLLBACK          = 80006;
    const EXCEPTION_CODE_UNEXPECTED               = 80009;
    /** Exceptionメッセージ */
    public static $EXCEPTION_MESSAGES = array(
        self::EXCEPTION_CODE_FAILED_CONNECTION        => 'Failed to database connection.',
        self::EXCEPTION_CODE_FAILED_CLOSE             => 'Failed to database connection close.',
        self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY     => 'Failed to execute query. "%s"',
        self::EXCEPTION_CODE_FAILED_BEGIN_TRANSACTION => 'Failed to begin transaction.',
        self::EXCEPTION_CODE_FAILED_COMMIT            => 'Failed to commit the database.',
        self::EXCEPTION_CODE_FAILED_ROLLBACK          => 'Failed to rollback the database.',
        self::EXCEPTION_CODE_UNEXPECTED               => 'An unexpected database error has occurred.'
    );

    /** 一時テーブル接頭辞 */
    const PREFIX_TEMPORARY_TABLE = 'TMP_';

    /** SQLフォーマット */
    const FORMAT_SQL_INSERT                 = 'INSERT INTO %s ';
    const FORMAT_SQL_UPDATE                 = 'UPDATE %s SET ';
    const FORMAT_SQL_SELECT                 = 'SELECT %s FROM %s';
    const FORMAT_SQL_DELETE                 = 'DELETE FROM %s';
    const FORMAT_SQL_TRUNCATE               = 'TRUNCATE %s';
    const FORMAT_SQL_EXPLAIN                = 'EXPLAIN %s';
    const FORMAT_SQL_SHOW_FULL_COLUMNS      = 'SHOW FULL COLUMNS FROM %s';
    const FORMAT_SQL_CREATE_TEMPORARY_TABLE = 'CREATE TEMPORARY TABLE %s LIKE %s';

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * 初期設定
     *
     * @param string $hostname
     * @param string $dbname
     * @param string $username
     * @param string $password
     * @param bool   $logging
     */
    public static function initialize($hostname, $dbname, $username, $password, $logging = true)
    {
        self::$hostname = $hostname;
        self::$dbname   = $dbname;
        self::$username = $username;
        self::$password = $password;
        self::$enable_logging  = $logging;
    }

    /**
     * 接続
     * @return null|PDO
     * @throws DbException
     */
    public static function connect()
    {
        try {
            if (self::$connection === null) {
                self::$connection = new PDO('mysql:charset=utf8; dbname=' . self::$dbname . '; host=' . self::$hostname, self::$username, self::$password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            if (self::ENABLE_QUERY_LOG) {
                Logger::database(Logger::createHeader('[' . date('Y/m/d H:i:s') . '] Open database connection'), false);
            }
            return self::$connection;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_CONNECTION], self::EXCEPTION_CODE_FAILED_CONNECTION);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 切断
     * @todo 1セッション毎に切断するようにしたいけどね
     * @throws DbException
     */
    public static function close()
    {

        try {
            if (self::$connection !== null) {
                self::$connection = null;
                if (self::ENABLE_QUERY_LOG) {
                    Logger::database(Logger::createHeader('[' . date('Y/m/d H:i:s') . '] Close database connection'), false);
                }
            }
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_CLOSE], self::EXCEPTION_CODE_FAILED_CLOSE);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * トランザクション内か否か
     * @return bool
     */
    public static function inTransaction()
    {
        if (self::$connection === null) {
            self::connect();
        }
        return self::$connection->inTransaction();
    }

    /**
     * トランザクション開始
     * @throws DbException
     */
    public static function begin()
    {

        if (self::$connection === null) {
            self::connect();
        }

        try{
            self::$connection->beginTransaction();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_BEGIN_TRANSACTION], self::EXCEPTION_CODE_FAILED_BEGIN_TRANSACTION);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * ロールバック
     * @throws DbException
     */
    public static function rollback()
    {

        if (self::$connection === null) {
            self::connect();
        }

        try{
            self::$connection->rollBack();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_ROLLBACK], self::EXCEPTION_CODE_FAILED_ROLLBACK);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * コミット
     * @throws DbException
     */
    public static function commit()
    {

        if (self::$connection === null) {
            self::connect();
        }

        try{
            self::$connection->commit();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_COMMIT], self::EXCEPTION_CODE_FAILED_COMMIT);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * 最後に発行したクエリを取得
     * 
     * @return string
     */
    public static function getLastQuery()
    {
        return self::$last_query;
    }


    /**
     * 自分でSQL書くやつ(更新系)
     *
     * @param  string $sql    SQL文
     * @param  array  $params パラメータ配列
     *
     * @return array  結果の配列
     * @throws DbException
     */
    public static function execute($sql, $params = array())
    {
        if (self::$connection === null) {
            self::connect();
        }

        try {
            self::startBenchmark();
            $sth    = self::$connection->prepare($sql);
            $result = $sth->execute($params);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark();
            return $result;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * 自分でSQL書くやつ(参照系)
     *
     * @param  string  $sql     SQL文
     * @param  array   $params  パラメータ配列
     * @param  boolean $explain EXPLAINを実行するか否か
     * @return array  結果の配列
     * @throws DbException
     */
    public static function query($sql, $params = array(), $explain = false)
    {

        if (self::$connection === null) {
            self::connect();
        }

        if ($explain === true) {
            return self::explain($sql, $params);
        }

        try{
            self::startBenchmark();
            $sth = self::$connection->prepare($sql);
            $sth->execute($params);
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark();
            return $result;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }


    }

    /**
     * 同じ構成の一時テーブルを作成する
     *
     * @param  string $table
     * @return string 一時テーブル名
     * @throws DbException
     */
    public static function createCloneTemporaryTable($table)
    {
        try{
            $tmp_table = self::PREFIX_TEMPORARY_TABLE . $table;
            self::execute(sprintf(self::FORMAT_SQL_CREATE_TEMPORARY_TABLE, $tmp_table, $table));
            return $tmp_table;
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * SELECT文発行(複数行)
     *
     * @param  string  $table   テーブル名
     * @param  string  $columns カラム名
     * @param  string  $suffix  条件等
     * @param  array   $params  パラメータ
     * @param  boolean $explain EXPLAINを実行するか否か
     * @return array   結果の配列
     * @throws DbException
     */
    public static function select($table, $columns = '*', $suffix = null, $params = array(), $explain = false)
    {

        if (self::$connection === null) {
            self::connect();
        }

        try{
            $sql  = sprintf(self::FORMAT_SQL_SELECT, $columns, $table);
            $sql .= ($suffix === null) ? '' : " {$suffix}";
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        if ($explain === true) {
            return self::explain($sql, $params);
        }

        try{
            self::startBenchmark();
            $sth = self::$connection->prepare($sql);
            $sth->execute($params);
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark();
            return (count($result) > 0) ? $result : null;
        } catch (PDOException $e) {
            Logger::dbException($e);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], self::$last_query), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }

    }

    /**
     * SELECT文発行(1行のみ)
     *
     * @param  string  $table   テーブル名
     * @param  string  $columns カラム名
     * @param  string  $suffix  条件等
     * @param  array   $params  パラメータ
     * @param  boolean $explain EXPLAINを実行するか否か
     * @return array|null 結果の配列
     * @throws DbException
     */
    public static function selectRow($table, $columns = '*', $suffix = null, $params = array(), $explain = false)
    {
        if (self::$connection === null) {
            self::connect();
        }

        try {
            $sql  = sprintf(self::FORMAT_SQL_SELECT, $columns, $table);
            $sql .= ($suffix === null) ? '' : " {$suffix}";
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        if ($explain === true) {
            return self::explain($sql, $params);
        }

        try{
            self::startBenchmark();
            $sth = self::$connection->prepare($sql);
            $sth->execute($params);
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark();
            return ($result !== false) ? $result : null;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }

    }

    /**
     * INSERT文発行
     *
     * @param  string $table テーブル名
     * @param  array  $data  挿入値 array('カラム名' => '値')
     * @return int 最後に挿入したID
     * @throws DbException
     */
    public static function insert($table, $data)
    {
        if (self::$connection === null) {
            self::connect();
        }

        try {
            $set_columns = array();
            $set_data    = array();
            $placeholder = array();
            $sql         = sprintf(self::FORMAT_SQL_INSERT, $table);
            foreach ($data as $column => $val) {
                array_push($set_columns, "`{$column}`");
                if ($val === self::NOW) {
                    array_push($placeholder, self::NOW);
                } else {
                    array_push($set_data, $val);
                    array_push($placeholder, '?');
                }
            }
            if ((array_key_exists(self::CREATED_AT_COLUMN_NAME, $data) === false) && self::isExistsColumn($table, self::CREATED_AT_COLUMN_NAME)) {
                array_push($set_columns, self::CREATED_AT_COLUMN_NAME);
                array_push($placeholder, self::NOW);
            }
            if ((array_key_exists(self::UPDATED_AT_COLUMN_NAME, $data) === false) && self::isExistsColumn($table, self::UPDATED_AT_COLUMN_NAME)) {
                array_push($set_columns, self::UPDATED_AT_COLUMN_NAME);
                array_push($placeholder, self::NOW);
            }
            $sql .= '(' . implode(', ', $set_columns) . ') ';
            $sql .= 'VALUES (' . implode(', ', $placeholder) . ')';
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        try{
            self::startBenchmark('INSERT');
            $sth = self::$connection->prepare($sql);
            $sth->execute($set_data);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $set_data, true));
            self::endBenchmark('INSERT');
            return self::$connection->lastInsertId();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }

    }

    /**
     * 複数行INSERT文発行
     *
     * @param  string $table テーブル名
     * @param  array  $data  挿入値 array(0 => array('カラム名' => '値'), 1 => array('カラム名' => '値'))
     * @return int    最後に挿入したID
     * @throws DbException
     */
    public static function insertMultipleRow($table, $data)
    {
        if (self::$connection === null) {
            self::connect();
        }

        try {
            $set_columns = array();
            $set_data    = array();
            $values      = array();
            $sql         = sprintf(self::FORMAT_SQL_INSERT, $table);

            //0から採番されてない配列の対策
            foreach ($data as $key => $val) {
                $data[] = $val;
                unset($data[$key]);
            }
            $data = array_merge($data);

            foreach ($data as $row_num => $row_data) {
                $placeholder = array();
                foreach ($row_data as $column => $val) {
                    if ((int)$row_num === 0) {
                        array_push($set_columns, "`{$column}`");
                    }
                    if ($val === self::NOW) {
                        array_push($placeholder, self::NOW);
                    } else {
                        array_push($set_data, $val);
                        array_push($placeholder, '?');
                    }
                }

                if ((array_key_exists(self::CREATED_AT_COLUMN_NAME, $row_data) === false) && self::isExistsColumn($table, self::CREATED_AT_COLUMN_NAME)) {
                    if ((int)$row_num === 0) {
                        array_push($set_columns, '`' . self::CREATED_AT_COLUMN_NAME . '`');
                    }
                    array_push($placeholder, self::NOW);
                }
                if ((array_key_exists(self::UPDATED_AT_COLUMN_NAME, $row_data) === false) && self::isExistsColumn($table, self::UPDATED_AT_COLUMN_NAME)) {
                    if ((int)$row_num === 0) {
                        array_push($set_columns, '`' . self::UPDATED_AT_COLUMN_NAME . '`');
                    }
                    array_push($placeholder, self::NOW);
                }

                array_push($values, '(' . implode(',', $placeholder) . ')');
            }
            $sql .= '(' . implode(', ', $set_columns) . ') VALUES ';
            $sql .= implode(', ', $values);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        try{
            self::startBenchmark('INSERT_MULTIPLE');
            $sth = self::$connection->prepare($sql);
            $sth->execute($set_data);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $set_data, true));
            self::endBenchmark('INSERT_MULTIPLE');
            return self::$connection->lastInsertId();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }

    }

    /**
     * 複数行INSERT ON DUPLICATE KEY UPDATE文発行
     *
     * @param string $table
     * @param array $data
     *
     * @return mixed
     * @throws DbException
     */
    public static function insertDuplicateKeyUpdateMultiple($table, $data)
    {

        if(self::$connection === null){
            self::connect();
        }
        $set_columns = array();
        $set_data    = array();
        $values     = array();
        $sql = "INSERT INTO {$table} ";
        foreach($data as $row_num => $row_data){
            $placeholder = array();
            foreach($row_data as $column => $val){
                if((int)$row_num === 0){
                    array_push($set_columns, "`{$column}`");
                }
                if($val === self::NOW){
                    array_push($placeholder, Db::NOW);
                }else{
                    array_push($set_data, $val);
                    array_push($placeholder, '?');
                }
            }

            if((array_key_exists(self::CREATED_AT_COLUMN_NAME, $row_data) === false) && self::isExistsColumn($table, self::CREATED_AT_COLUMN_NAME)){
                if((int)$row_num === 0){
                    array_push($set_columns, '`' . self::CREATED_AT_COLUMN_NAME . '`');
                }
                array_push($placeholder, Db::NOW);
            }
            if((array_key_exists(self::UPDATED_AT_COLUMN_NAME, $row_data) === false) && self::isExistsColumn($table, self::UPDATED_AT_COLUMN_NAME)){
                if((int)$row_num === 0){
                    array_push($set_columns, '`' . self::UPDATED_AT_COLUMN_NAME . '`');
                }
                array_push($placeholder, Db::NOW);
            }

            array_push($values, '(' . implode(',', $placeholder) . ')');
        }
        $sql .= '(' . implode(', ', $set_columns) . ') VALUES ';
        $sql .= implode(', ', $values);
        $sql .= 'ON DUPLICATE KEY UPDATE ';
        $duplicate_sql = array();
        foreach($set_columns as $column) {
            if($column === '`id`') {
                continue;
            }
            array_push($duplicate_sql,  "{$column} = VALUES({$column})");
        }
        $sql .= implode(', ', $duplicate_sql);

        try{
            self::startBenchmark('INSERT_DUPLICATE_KEY_UPDATE');
            $sth = self::$connection->prepare($sql);
            $sth->execute($set_data);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $set_data, true));
            self::endBenchmark('INSERT_DUPLICATE_KEY_UPDATE');
            return self::$connection->lastInsertId();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }


    }

    /**
     * UPDATE文発行
     *
     * @param  string $table  テーブル名
     * @param  array  $data   更新値 array('カラム名' => '値')
     * @param  string $suffix 条件等
     * @param  array  $params パラメータ
     * @return boolean
     * @throws DbException
     */
    public static function update($table, $data, $suffix = null, $params = array())
    {

        if (self::$connection === null) {
            self::connect();
        }

        try {
            $set_columns = array();
            $bind_params = array();
            $sql         = sprintf(self::FORMAT_SQL_UPDATE, $table);
            foreach ($data as $column => $val) {
                if ($val === self::NOW) {
                    array_push($set_columns, "`{$column}`=" . Db::NOW);
                } else {
                    array_push($set_columns, "`{$column}`=:{$column}");
                    $bind_params[$column] = $val;
                }
            }
            if ((array_key_exists(self::UPDATED_AT_COLUMN_NAME, $data) === false) && self::isExistsColumn($table, self::UPDATED_AT_COLUMN_NAME)) {
                array_push($set_columns, self::UPDATED_AT_COLUMN_NAME . '=' . Db::NOW);
            }
            if (count($params) > 0) {
                foreach ($params as $name => $val) {
                    $bind_params[$name] = $val;
                }
            }
            $sql .= implode(', ', $set_columns);
            $sql .= ($suffix === null) ? '' : " {$suffix}";
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        try{
            self::startBenchmark('UPDATE');
            $sth = self::$connection->prepare($sql);
            foreach ($bind_params as $column => $val) {
                $sth->bindValue(":{$column}", $val);
            }
            $result = $sth->execute();
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $bind_params));
            self::endBenchmark('UPDATE');
            return $result;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * DELETE文発行
     *
     * @param  string $table  テーブル名
     * @param  string $suffix 条件等
     * @param  array  $params パラメータ
     * @return int 削除された件数
     * @throws DbException
     */
    public static function delete($table, $suffix = null, $params = array())
    {

        if (self::$connection === null) {
            self::connect();
        }

        try {
            $sql  = sprintf(self::FORMAT_SQL_DELETE, $table);
            $sql .= ($suffix === null) ? '' : " {$suffix}";
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

        try{
            self::startBenchmark('DELETE');
            $sth = self::$connection->prepare($sql);
            $sth->execute($params);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark('DELETE');
            return $sth->rowCount();
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        }

    }

    /**
     * EXPLAINする
     *
     * @param  string $sql    SQL文
     * @param  array  $params パラメータ配列
     * @return array
     * @throws DbException
     */
    public static function explain($sql, $params = array())
    {

        if (self::$connection === null) {
            self::connect();
        }

        try{
            self::startBenchmark('EXPLAIN');
            $sth = self::$connection->prepare(sprintf(self::FORMAT_SQL_EXPLAIN, $sql));
            $sth->execute($params);
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            self::$last_query = self::setQueryStack(self::getBoundQuery($sql, $params));
            self::endBenchmark('EXPLAIN');
            return $result;
        } catch (PDOException $e) {
            Logger::dbException($e);
            throw new DbException(sprintf(self::$EXCEPTION_MESSAGES[self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY], $sql), self::EXCEPTION_CODE_FAILED_EXECUTE_QUERY);
        } catch (Exception $e) {
            Logger::dbException($e);
            throw new DbException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * DESCRIBEする
     *
     * @param  string  $table            テーブル名
     * @param  boolean $only_column_name カラム名のみ取得するか否か
     *
     * @return array   結果の配列
     * @throws DbException
     */
    public static function describe($table, $only_column_name = true)
    {

        if (isset(self::$describe[$table]) === false) {
            try{
                $result   = self::query(sprintf(self::FORMAT_SQL_SHOW_FULL_COLUMNS, $table));
                $describe = [];
                foreach ($result as $row) {
                    $describe[$row['Field']] = [
                        'name'     => $row['Field'],
                        'type'     => strtoupper($row['Type']),
                        'not_null' => ($row['Null'] === 'NO') ? true : false,
                        'key'      => $row['Key'],
                        'default'  => $row['Default'],
                        'extra'    => $row['Extra'],
                        'comment'  => $row['Comment']
                    ];
                }
                self::$describe[$table] = $describe;
            } catch (Exception $e) {
                Logger::dbException($e);
                throw new DbException($e->getMessage(), $e->getCode());
            }
        }

        return ($only_column_name) ? array_column(self::$describe[$table], 'name') : self::$describe[$table];

    }

    /**
     * カラムが存在するか調べる
     *
     * @param string $table       テーブル名
     * @param string $column_name カラム名
     *
     * @return boolean
     */
    public static function isExistsColumn($table, $column_name)
    {
        $columns = self::describe($table);
        return in_array($column_name, $columns);
    }

    /**
     * カウントする
     *
     * @param  string $table  テーブル名
     * @param  string $suffix 条件等
     * @param  array  $params パラメータ配列
     * @return int カウント結果
     */
    public static function count($table, $suffix = null, $params = array())
    {
        $result  = self::selectRow($table, 'COUNT(*) AS count', $suffix, $params);
        return ($result !== null) ? (int)$result['count'] : 0;
    }

    /**
     * TRUNCATEする
     *
     * @param string $table
     *
     * @return array
     */
    public static function truncate($table)
    {
        self::execute('SET FOREIGN_KEY_CHECKS = 0');
        $result = self::execute(sprintf(self::FORMAT_SQL_TRUNCATE, $table));
        self::execute('SET FOREIGN_KEY_CHECKS = 1');
        return $result;
    }

    /**
     * バインド済みクエリを生成して返す
     * @param string $sql
     * @param array  $params
     * @param bool   $is_insert
     *
     * @return mixed
     */
    private static function getBoundQuery($sql, $params = array(), $is_insert = false)
    {

        if ($is_insert) {
            $format = str_replace('?', "'%s'", $sql);
            $sql = vsprintf($format, $params);
        } else {
            foreach ($params as $key => $val) {
                $search = ":{$key}";
                $sql = str_replace($search, "'{$val}'", $sql);
            }
        }

        return $sql;
    }

    /**
     * クエリスタックに格納してラストクエリを返す
     *
     * @param string $sql
     *
     * @return string
     */
    private static function setQueryStack($sql)
    {
        self::$query_stack[] = $sql;
        return $sql;
    }

    /**
     * ベンチマーク開始
     * @param string $namespace
     */
    private static function startBenchmark($namespace = 'default')
    {
        if (self::ENABLE_BENCHMARK || self::ENABLE_SLOW_QUERY_LOG) {
            Benchmark::start($namespace);
        }
    }

    /**
     * ベンチマーク終了
     * @param string $namespace
     */
    private static function endBenchmark($namespace = 'default')
    {

        if (self::ENABLE_BENCHMARK || self::ENABLE_SLOW_QUERY_LOG) {
            Benchmark::end($namespace);
            $result = Benchmark::getResult($namespace);
            self::$last_query_execution_time = $result['raw_execution_time'];
            self::$query_log_prefix  = sprintf('[Time:%s]', $result['execution_time']);
            self::$query_log_message = self::$query_log_prefix . self::$last_query . PHP_EOL;
        }

        if (self::ENABLE_SLOW_QUERY_LOG && (self::$last_query_execution_time >= self::SLOW_QUERY_BORDER_SEC)) {
            self::$query_log_message = sprintf('[Slow]%s', self::$query_log_message);
        }

        if (self::ENABLE_QUERY_LOG) {
            self::$query_log_message .= Logger::createLine();
            Logger::database(self::$query_log_message);
        }

    }

}

/**
 * DbException
 */
class DbException extends Exception
{

    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

}
