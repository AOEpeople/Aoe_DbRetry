<?php

class Aoe_DbRetry_Resource_Db_Pdo_Mysql_Adapter extends Magento_Db_Adapter_Pdo_Mysql
{
    protected $retryOnMessages = [
        'SQLSTATE[HY000]: General error: 2013 Lost connection to MySQL server during query',
        'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction',
        'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction',
    ];

    /**
     * Special handling for PDO query().
     * All bind parameter names must begin with ':'.
     *
     * @param string|Zend_Db_Select $sql  The SQL statement with placeholders.
     * @param mixed                 $bind An array of data or data itself to bind to the placeholders.
     *
     * @return Zend_Db_Statement_Pdo
     * @throws Zend_Db_Adapter_Exception To re-throw PDOException.
     */
    public function query($sql, $bind = [])
    {
        $this->_debugTimer();
        $result = null;
        try {
            $this->_checkDdlTransaction($sql);
            $this->_prepareQuery($sql, $bind);

            $maxTries = 1 + (isset($this->_config['retries']) ? min(max(intval($this->_config['retries']), 0), 5) : 5);
            $retryPower = (isset($this->_config['retry_power']) ? min(max(intval($this->_config['retry_power']), 1), 5) : 2);
            $try = 0;
            while ($try < $maxTries) {
                try {
                    $result = Zend_Db_Adapter_Pdo_Mysql::query($sql, $bind);
                    $try = $maxTries;
                } catch (Exception $e) {
                    $try++;
                    Mage::log("Max retry:{$maxTries} retry power:{$retryPower}", Zend_Log::DEBUG);
                    Mage::dispatchEvent('aoe_dbretry_exception', ['try' => $try, 'exception' => $e]);
                    if ($try < $maxTries) {
                        $message = null;
                        if ($e instanceof PDOException) {
                            $message = $e->getMessage();
                        } elseif ($e->getPrevious() instanceof PDOException) {
                            $message = $e->getPrevious()->getMessage();
                        } else {
                            Mage::log("Exception is instance of " . get_class($e), Zend_Log::DEBUG);
                            Mage::log("Previous Exception is instance of " . get_class($e->getPrevious()), Zend_Log::DEBUG);
                        }
                        if ($message && in_array($message, $this->retryOnMessages)) {
                            $sleepSeconds = pow($try, $retryPower);
                            Mage::log("Retrying query [retry:{$try} delay:{$sleepSeconds}]: {$message}", Zend_Log::DEBUG);
                            if ($try === 1) {
                                Mage::logException($e);
                            }
                            sleep($sleepSeconds);
                            continue;
                        }
                    }

                    throw $e;
                }
            }
        } catch (Exception $e) {
            $this->_debugStat(self::DEBUG_QUERY, $sql, $bind);
            $this->_debugException($e);
        }
        $this->_debugStat(self::DEBUG_QUERY, $sql, $bind, $result);

        return $result;
    }

    /**
     * Run RAW Query
     *
     * @param string $sql
     *
     * @return Zend_Db_Statement_Interface
     * @throws PDOException
     */
    public function raw_query($sql)
    {
        try {
            return $this->query($sql);
        } catch (Zend_Db_Statement_Exception $e) {
            // Convert to PDOException to maintain backwards compatibility with usage of MySQL adapter
            $e = $e->getPrevious();
            if (!$e instanceof PDOException) {
                $e = new PDOException($e->getMessage(), $e->getCode());
            }
            throw $e;
        }
    }
}
