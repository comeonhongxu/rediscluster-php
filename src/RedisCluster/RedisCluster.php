<?php
/*
 * This file is part of the RedisCluster package.
 *
 * (c) Salimane Adjao Moustapha <me@salimane.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RedisCluster;

/**
 * Implementation of the RedisCluster Client using phpredis extension Redis class
 * This abstract class provides a php interface to all Redis commands on the cluster of redis servers.
 * and implementing how the commands are sent to and received from the cluster.
 *
 * @author Salimane Adjao Moustapha <me@salimane.com>
 */
class RedisCluster {

  /**
   * servers array used during construct
   * @var array
   * @access public
   */
  public $cluster;

  /**
   * number of servers array used during construct
   * @var array
   * @access public
   */
  public $no_servers;

  /**
   * Collection of Redis objects attached to Redis servers
   * @var array
   * @access private
   */
  private $redises;

  /**
   * instance of the Redis class from php extension
   * @var resource
   * @access private
   */
  private $__redis;


  /**
   * The read commands
   * @var array
   * @access private
   */
  private static $read_keys = array(
    'debug' => 'debug', 'getbit' => 'getbit',
    'get' => 'get', 'getrange' => 'getrange', 'hget' => 'hget',
    'hgetall' => 'hgetall', 'hkeys' => 'hkeys', 'hlen' => 'hlen', 'hmget' => 'hmget',
    'hvals' => 'hvals', 'lindex' => 'lindex', 'llen' => 'llen',
    'lrange' => 'lrange', 'object' => 'object',
    'scard' => 'scard', 'sismember' => 'sismember', 'smembers' => 'smembers',
    'srandmember' => 'srandmember', 'strlen' => 'strlen', 'type' => 'type',
    'zcard' => 'zcard', 'zcount' => 'zcount', 'zrange' => 'zrange', 'zrangebyscore' => 'zrangebyscore',
    'zrank' => 'zrank', 'zrevrange' => 'zrevrange', 'zrevrangebyscore' => 'zrevrangebyscore',
    'zrevrank' => 'zrevrank', 'zscore' => 'zscore',
    'mget' => 'mget', 'bitcount' => 'bitcount', 'echo' => 'echo', 'substr' => 'substr',
    'getMultiple' => 'getMultiple',
    'lSize' => 'lSize', 'lsize' => 'lsize', 'lGetRange' => 'lGetRange',
    'sContains' => 'sContains', 'sSize' => 'sSize', 'sGetMembers' => 'sGetMembers',
    'zSize' => 'zSize',
  );

  /**
   * The write commands
   * @var array
   * @access private
   */
  private static $write_keys = array(
    'append' => 'append', 'blpop' => 'blpop', 'brpop' => 'brpop', 'brpoplpush' => 'brpoplpush',
    'decr' => 'decr', 'decrby' => 'decrby', 'del' => 'del', 'exists' => 'exists', 'hexists' => 'hexists',
    'expire' => 'expire', 'expireat' => 'expireat', 'getset' => 'getset', 'hdel' => 'hdel',
    'hincrby' => 'hincrby', 'hset' => 'hset', 'hsetnx' => 'hsetnx', 'hmset' => 'hmset',
    'incr' => 'incr', 'incrby' => 'incrby', 'linsert' => 'linsert', 'lpop' => 'lpop',
    'lpush' => 'lpush', 'lpushx' => 'lpushx', 'lrem' => 'lrem', 'lset' => 'lset',
    'ltrim' => 'ltrim', 'move' => 'move',
    'persist' => 'persist', 'publish' => 'publish', 'psubscribe' => 'psubscribe', 'punsubscribe' => 'punsubscribe',
    'rpop' => 'rpop', 'rpoplpush' => 'rpoplpush', 'rpush' => 'rpush',
    'rpushx' => 'rpushx', 'sadd' => 'sadd', 'sdiff' => 'sdiff', 'sdiffstore' => 'sdiffstore',
    'set' => 'set', 'setbit' => 'setbit', 'setex' => 'setex', 'setnx' => 'setnx',
    'setrange' => 'setrange', 'sinter' => 'sinter', 'sinterstore' => 'sinterstore', 'smove' => 'smove',
    'sort' => 'sort', 'spop' => 'spop', 'srem' => 'srem', 'subscribe' => 'subscribe',
    'sunion' => 'sunion', 'sunionstore' => 'sunionstore', 'unsubscribe' => 'unsubscribe', 'unwatch' => 'unwatch',
    'watch' => 'watch', 'zadd' => 'zadd', 'zincrby' => 'zincrby', 'zinterstore' => 'zinterstore',
    'zrem' => 'zrem', 'zremrangebyrank' => 'zremrangebyrank', 'zremrangebyscore' => 'zremrangebyscore', 'zunionstore' => 'zunionstore',
    'mset' => 'mset','msetnx' => 'msetnx', 'rename' => 'rename', 'renamenx' => 'renamenx',
    'del' => 'del', 'ttl' => 'ttl', 'flushall' => 'flushall', 'flushdb' => 'flushdb',
    'renameKey' => 'renameKey',
    'listTrim' => 'listTrim', 'lRemove' => 'lRemove', 'sRemove' => 'sRemove',
    'setTimeout' => 'setTimeout', 'zDelete' => 'zDelete',
    'zDeleteRangeByScore' => 'zDeleteRangeByScore', 'zDeleteRangeByRank' => 'zDeleteRangeByRank',
  );

  /**
   * The commands that are not subject to hashing
   * @var array
   * @access private
   */
  private static $dont_hash = array(
    'auth' => 'auth', 'monitor' => 'monitor', 'quit' => 'quit',
    'shutdown' => 'shutdown', 'slaveof' => 'slaveof', 'slowlog' => 'slowlog', 'sync' => 'sync',
    'discard' => 'discard', 'exec' => 'exec', 'multi' => 'multi',
    'setOption' => 'setOption', 'getOption' => 'getOption'
  );

  /**
   * The commands that are needs the keys they are processing to be tagged
   * when hashing for the cluster
   * @var array
   * @access private
   */
  private static $tag_keys = array(
      'mget' => 'mget', 'rename' => 'rename', 'renamenx' => 'renamenx',
      'mset' => 'mset', 'msetnx' => 'msetnx',
      'brpoplpush' => 'brpoplpush', 'rpoplpush' => 'rpoplpush',
      'sdiff' => 'sdiff', 'sdiffstore' => 'sdiffstore',
      'sinter' => 'sinter', 'sinterstore' => 'sinterstore',
      'sunion' => 'sunion', 'sunionstore' => 'sunionstore',
      'smove' => 'smove', 'zinterstore' => 'zinterstore',
      'zunionstore' => 'zunionstore', 'sort' => 'sort'
  );

  /**
   * The commands that could be sent to all the servers and return the aggregrate results
   * @var array
   * @access private
   */
  private static $loop_keys = array(
      'keys' => 'keys', 'getkeys' => 'getkeys',
      'select' => 'select',
      'save' => 'save', 'bgsave' => 'bgsave',
      'bgrewriteaof' => 'bgrewriteaof',
      'dbsize' => 'dbsize', 'info' => 'info',
      'lastsave' => 'lastsave', 'ping' => 'ping',
      'flushall' => 'flushall', 'flushdb' => 'flushdb',
      'randomkey' => 'randomkey', 'sync' => 'sync',
      'config' => 'config', 'time' => 'time'
  );

  /**
   * Creates a Redis interface to a cluster of Redis servers
   * @param array $cluster The Redis servers in the cluster.
   */
  function __construct($cluster, $redisdb = 0) {
    //die when wrong server array
    if(empty($cluster['nodes']) || empty($cluster['master_of'])) {
      error_log("RedisCluster: Please set a correct array of redis servers.", 0); die();
    }

    $this->cluster = $cluster;
    $this->no_servers = count($cluster['master_of']);
    $slaves = array_values($cluster['master_of']);

    //connect to all servers
    foreach ($cluster['nodes'] as $alias => $server) {
      $this->__redis = new \Redis();
      try {
        $this->__redis->pconnect($server['host'], $server['port']);
        $sla = $this->__redis->config('GET', 'slaveof');
        if (in_array($alias, $slaves) && $sla['slaveof'] == '') {
          error_log("RedisCluster: server " . $server['host'] .':'. $server['port'] . " is not a slave.", 0); die();
        }
        $this->__redis->select($redisdb);
      }
      catch(RedisException $e) {
        try {
          $this->__redis->pconnect($server['host'], $server['port']);
          $sla = $this->__redis->config('GET', 'slaveof');
          if (in_array($alias, $slaves) && $sla['slaveof'] == '') {
            error_log("RedisCluster: server " . $server['host'] .':'. $server['port'] . " is not a slave.", 0); die();
          }
          $this->__redis->select($redisdb);
        }
        catch(RedisException $e) {
          //if node is slave and is down, replace its connection with its master's
          $ms = array_search($alias, $this->cluster['master_of']);
          if(!empty($ms) && ($sla['slaveof'] != '' || $cluster['nodes'][$ms] == $cluster['nodes'][$alias])) {
            try {
              $this->__redis->pconnect($cluster['nodes'][$ms]['host'], $cluster['nodes'][$ms]['port']);
              $this->__redis->select($redisdb);
            }
            catch(RedisException $e) {
              try {
                $this->__redis->pconnect($cluster['nodes'][$ms]['host'], $cluster['nodes'][$ms]['port']);
                $this->__redis->select($redisdb);
              }
              catch(RedisException $e) {
                error_log("RedisCluster cannot connect to: " . $cluster['nodes'][$ms]['host'] .':'. $cluster['nodes'][$ms]['port'], 0); die();
              }
            }
            $this->redises[$alias] =  $this->__redis;
          } else {
            error_log("RedisCluster cannot connect to: " . $server['host'] .':'. $server['port'], 0); die();
          }
        }
      }
      $this->redises[$alias] =  $this->__redis;
    }
  }


  /**
   * select a db on all the servers
   * @param redisdb The redis db to be selected.
   */
  public function setSelectDB($redisdb = 0) {
    //select new db for to all servers
    foreach ($this->redises as $alias => $server) {
      try {
        $server->select($redisdb);
      } catch(RedisException $e) {
        error_log("RedisCluster setSelectDB : " . $e->getMessage(). " on " . $this->cluster['nodes'][$alias]['host'] . ':' . $this->cluster['nodes'][$alias]['port'] . "  db $redisdb", 0); die;
      }
      $this->redises[$alias] =  $server;
    }
  }


  /**
   * Magic method to handle all function requests
   *
   * @param string $name The name of the method called.
   * @param array $args Array of supplied arguments to the method.
   * @return mixed Return value from Redis::__call() based on the command.
   */
  function __call($name, $args){
    if (!isset(self::$loop_keys[$name])) {
      $tag_start = false;
      is_string($args[0]) && $tag_start = strrpos($args[0], '{');

      //trigger error msg on banned keys unless u're using it with tagged keys e.g. "bar{zap}"
      if (isset(self::$tag_keys[$name]) && !$tag_start) {
        if (is_callable(array($this, "_rc_$name"))) {
          $name = "_rc_$name";
          $argcount = count($args);
          if (1 == $argcount)
            return $this->$name($args[0]);
          elseif(2 == $argcount)
            return $this->$name($args[0], $args[1]);
          else
            return call_user_func_array(array($this, $name), $args);
        } else {
          throw new \Exception("RedisCluster: Command $name Not Supported (each key name has its own node)");
        }
      }
      //get the hash key depending on tags or not
      $hkey = $args[0];
      //take care of tagged key names for forcing multiple keys on the same node, e.g. $redis->set("bar{zap}", "bar")
      if ($tag_start) {
        $hkey = substr($args[0], $tag_start+1, -1);
        $args[0] = substr($args[0], 0, $tag_start);
      }

      //get the node number
      $node = $this->_getnodenamefor($hkey);
      $redisent = $this->redises[$this->cluster['default_node']];
      if (isset(self::$write_keys[$name])) {
        $redisent = $this->redises[$node];
      } else if (isset(self::$read_keys[$name])) {
        $redisent = $this->redises[$this->cluster['master_of'][$node]];
      }
      // Execute the command on the server
      try {
        $argcount = count($args);
        if (1 == $argcount)
          return $redisent->$name($args[0]);
        elseif(2 == $argcount)
          return $redisent->$name($args[0], $args[1]);
        else
          return call_user_func_array(array($redisent, $name), $args);

      } catch(RedisException $e) {
        error_log("RedisCluster: " . $e->getMessage()." on $name on " . $this->cluster['nodes']['node_' . $node]['host'] .':'. $this->cluster['nodes']['node_' . $node]['port'], 0);
        return null;
      }
    }
    else {
      $result = array();
      foreach ($this->redises as $alias => $redisent) {

        try {
          if (isset(self::$write_keys[$name]) && !isset($this->cluster['master_of'][$alias])) {
            $res = null;
          }
          else {
            $res = call_user_func_array(array($redisent, $name), $args);
          }
        } catch(RedisException $e) {
          error_log("RedisCluster __call function: " . $e->getMessage() . " on $name on " . $this->cluster['nodes']['node_' . $node]['host'] .':'. $this->cluster['nodes']['node_' . $node]['port'], 0);
        }
        if ($name == 'keys' || $name == 'getKeys')
          $result += $res;
        else
          $result[$alias] = $res;
      }
      return $result;
    }
  }

  /**
   * Return the node name where the ``name`` would land to
   * @param string $name
   */
  private function _getnodenamefor($name) {
    return 'node_' . ((abs(crc32($name)) % $this->no_servers) + 1);
  }

  /**
   * Return the node where the ``name`` would land to
   * @param string $name
   */
  public function getnodefor($name) {
    $node = $this->_getnodenamefor($name);
    return array($node => $this->cluster['nodes'][$node]);
  }

  /**
   * Return the encoding, idletime, or refcount about the key
   * @param string $infotype
   * @param string $key
   */
  public function object($infotype, $key) {
    $redisent = $this->redises[$this->cluster['master_of'][$this->_getnodenamefor($key)]];
    return $redisent->object($infotype, $key);
  }

  /**
   * Pop a value off the tail of ``src``, push it on the head of ``dst`` and then return it.
   * This command blocks until a value is in ``src`` or until ``timeout``
   * seconds elapse, whichever is first. A ``timeout`` value of 0 blocks
   * forever.
   * Not atomic
   */
  private function _rc_brpoplpush() {
    $args = func_get_args();
    $src = array_shift($args);
    $dst = array_shift($args);
    $timeout = array_shift($args);
    $rpop = $this->brpop($src, $timeout);
    if (!empty($rpop)) {
      $this->lpush($dst, $rpop[1]);
      return $rpop[1];
    }
    return false;
  }

  /**
   * RPOP a value off of the ``src`` list and LPUSH it
   * on to the ``dst`` list.  Returns the value.
   */
  private function _rc_rpoplpush() {
    $args = func_get_args();
    $src = array_shift($args);
    $rpop = $this->rpop($src);
    if ($rpop) {
      $dst = array_shift($args);
      if ($this->lpush($dst, $rpop))
        return $rpop;
    }
    return false;
  }

  /**
   * Returns the members of the set resulting from the difference between
   * the first set and all the successive sets.
   */
  private function _rc_sdiff() {
    $args = func_get_args();
    $src = array_shift($args);
    $src_set = $this->smembers($src);
    if (!empty($src_set)) {
      foreach ($args as $key) {
        $res = $this->smembers($key);
        if (false === $res)
          return false;
        if (!empty($res))
          $src_set = array_diff($src_set, $res);
      }
      return array_values($src_set);
    }
    return $src_set;
  }

  /**
   * Store the difference of sets ``src``,  ``args`` into a new
   * set named ``dest``.  Returns the number of keys in the new set.
   */
  private function _rc_sdiffstore() {
    $args = func_get_args();
    $dst = array_shift($args);
    $result = call_user_func_array(array($this, 'sdiff'), $args);
    if (!empty($result)) {
      $res = 0;
      foreach($result as $k => $v)
        $res += (int)$this->sadd($dst, $v);
      return $res;
    }
    return 0;
  }

  /**
   * Returns the members of the set resulting from the difference between
   * the first set and all the successive sets.
   */
  private function _rc_sinter() {
    $args = func_get_args();
    $src = array_shift($args);
    $src_set = $this->smembers($src);
    if (!empty($src_set)) {
      foreach ($args as $key) {
        $res = $this->smembers($key);
        if (false === $res)
          return false;
        if (!empty($res))
          $src_set = array_intersect($src_set, $res);
      }
      return array_values($src_set);
    }
    return $src_set;
  }

  /**
   * Store the difference of sets ``src``,  ``args`` into a new
   * set named ``dest``.  Returns the number of keys in the new set.
   */
  private function _rc_sinterstore() {
    $args = func_get_args();
    $dst = array_shift($args);
    $result = call_user_func_array(array($this, 'sinter'), $args);
    if (!empty($result)) {
      $res = 0;
      foreach($result as $k => $v)
        $res += (int)$this->sadd($dst, $v);
      return $res;
    }
    return 0;
  }

  /**
   * Move ``value`` from set ``src`` to set ``dst``
   * not atomic
   */
  private function _rc_smove($src, $dst, $value) {
    if ($this->srem($src, $value))
      return bool($this->sadd($dst, $value));
    return false;
  }

  /**
   * Returns the members of the set resulting from the union between
   * the first set and all the successive sets.
   */
  private function _rc_sunion() {
    $args = func_get_args();
    $src = array_shift($args);
    $src_set = $this->smembers($src);
    if (!empty($src_set)) {
      foreach($args as $key){
        $res = $this->smembers($key);
        if (false === $res)
          return false;
        if (!empty($res))
          $src_set = array_unique(array_merge($src_set, $res));
      }
      return array_values($src_set);
    }
    return $src_set;
  }

  /**
   * Store the union of sets ``src``,  ``args`` into a new
   * set named ``dest``.  Returns the number of keys in the new set.
   */
  private function _rc_sunionstore() {
    $args = func_get_args();
    $dst = array_shift($args);
    $result = call_user_func_array(array($this, 'sunion'), $args);
    if (!empty($result)) {
      $res = 0;
      foreach($result as $k => $v)
        $res += (int)$this->sadd($dst, $v);
      return $res;
    }
    return 0;
  }

  /**
   * Sets each key in the ``args`` dict to its corresponding value
   */
  private function _rc_mset() {
    $args = func_get_args();
    $args = array_shift($args);
    $result = true;
    foreach($args as $k => $v)
      $result = $result && $this->set($k, $v);
    return $result;
  }

  /**
   * Sets each key in the ``args`` dict to its corresponding value if
   * none of the keys are already set
   */
  private function _rc_msetnx() {
    $args = func_get_args();
    $args = array_shift($args);
    foreach($args as $k => $v)
      if ($this->exists($k))
        return false;
    $result = true;
    return $this->_rc_mset($args);
  }

  /**
   * Returns a list of values ordered identically to ``$args``
   */
  private function _rc_mget() {
    $args = func_get_args();
    $result = array();
    foreach($args as $key)
      $result[] = $this->get($key);
    return $result;
  }

}