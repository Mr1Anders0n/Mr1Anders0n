<?php

include_once('Config.php');

class DB
    {
        private $connection;
        private $query = '';
        private $tableName = '';
        private $getFields = '';
        private $condition = '';
        private $limit = '';
        private $order = '';
        private $join = '';


        public function __construct()
            {
                $config = Config::db();

                $this->connection
                    = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

                if (mysqli_connect_errno())
                    {
                        throw new PDOException();
                    }
            }

        public static function __callStatic($name, $arguments)
            {
                $object = new DB;

                $object->setTableName($name);

                if(!empty($arguments)){
                    $object->insert($arguments[0]);
                }

                return $object;
            }

        public function setTableName($name)
            {
                $this->tableName = $name;

                return $this;
            }

        public function setGetFields($fields)
            {
                if (empty($fields))
                    {
                        $this->getFields = '*';
                    }
                else
                    {
                        $this->getFields = $this->sanitize(implode(',', $fields));
                    }

                return $this;
            }

        public function join($joinTableName, $joinTableId, $mainTableName, $mainTableId)
            {
                $this->join .= ' join ' . $joinTableName
                    . ' on ' . $joinTableName . '.' . $joinTableId
                    . ' = ' . $mainTableName . '.' . $mainTableId . ' ';

                return $this;
            }

        public function where($field, $operation, $operand)
            {
                $this->condition = ' where ' . $this->sanitize($field)
                    . ' ' . $this->sanitize($operation)
                    . ' \'' . $this->sanitize($operand) . '\'';

                return $this;
            }

        public function andWhere($field, $operation, $operand)
            {
                $this->condition .= ' and ' . $this->sanitize($field)
                    . ' ' . $this->sanitize($operation)
                    . ' \'' . $this->sanitize($operand) . '\'';

                return $this;
            }

        public function getLastId($idField = 'id'){
              $this->query = 'select ' . $idField
                  . ' from ' . $this->tableName
                  . ' ORDER by '. $idField . ' DESC limit 1';

              $res = $this->execute();

              return $res[0][$idField];
        }

        public function orderBy($column, $order = 'asc')
            {
                $this->order = ' order by ' . $this->sanitize($column)
                    . ' ' . $this->sanitize($order);

                return $this;
            }

        public function limit($number)
            {
                $this->limit = ' limit ' . $this->sanitize($number);

                return $this;
            }

        public function get($fields = [])
            {
                $this->setGetFields($fields);

                $this->query = 'select ' . $this->getFields
                    . ' from ' . $this->tableName
                    . $this->join
                    . $this->condition
                    . $this->order
                    . $this->limit;

                return $this->execute();
            }

        public function update($data = [])
            {
                $this->query = 'update ' . $this->tableName
                    . ' set ';
                foreach ($data as $key => $value){
                    $this->query .= $this->sanitize($key) . '=\'' . $this->sanitize($value).'\' ';
                }

                $this->query .= $this->condition;

                $this->execute();
            }

        public function insert($data = [])
            {
                $this->query = 'insert into ' . $this->tableName . '(';
                $flag = true;
                foreach ($data as $key => $value){
                    if ($flag){
                        $flag = false;
                        $this->query .= $this->sanitize($key);
                    } else {
                        $this->query .= ',' . $this->sanitize($key);
                    }
                }

                $this->query .= ') values (';

                $flag = true;

                foreach ($data as $value){
                    if ($flag){
                        $flag = false;
                        $this->query .= "'" . $this->sanitize($value) . "'";
                    } else {
                        $this->query .= ',' . "'" . $this->sanitize($value) . "'";
                    }
                }

                $this->query .= ')';

                return $this->execute();
            }

        public function destroy($id = null, $idField)
            {
                $this->query = 'delete from ' . $this->tableName;

                if (!is_null($id)) {
                    $this->where($idField, '=', $id);
                    $this->query .= $this->condition;
                }

                return $this->execute();
            }

        private function execute()
            {
                $result = $this->connection->query($this->query);

                if (is_bool($result)) {
                  echo mysqli_error($this->connection);
                  return $result;
                }

                if($result->num_rows < 2)
                    {
                        return [$result->fetch_array(MYSQLI_ASSOC)];
                    }
                return $result->fetch_all(MYSQLI_ASSOC);
            }

        private function sanitize($string)
            {
                return $this->connection->real_escape_string($string);
            }


    }
