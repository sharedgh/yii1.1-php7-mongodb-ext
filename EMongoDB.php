<?php

/**
 * EMongoDB
 *
 * This is merge work of tyohan, Alexander Makarov and mine
 * @since v1.0
 */
class EMongoDB extends CApplicationComponent
{
	/**
     * @var string host:port
     *
     * Correct syntax is:
     * mongodb://[username:password@]host1[:port1][,host2[:port2:],...]
     *
     * @example mongodb://localhost:27017
     * @since v1.0
     */
    public $connectionString;
    public $dbName = null;
    public $manager;
    public $bulk;

	/**
	 * Returns Mongo connection instance if not exists will create new
	 *
	 * @return Mongo
	 * @throws EMongoException
	 * @since v1.0
	 */
	public function getConnection()
	{
		if($this->manager === null)
		{
			try
			{
				Yii::trace('Opening MongoDB connection', 'ext.MongoDb.EMongoDB');
				if(empty($this->connectionString))
					throw new EMongoException(Yii::t('yii', 'EMongoDB.connectionString cannot be empty.'));
                    $this->manager = new MongoDB\Driver\Manager($this->connectionString);
                    $this->bulk = new MongoDB\Driver\BulkWrite;
				return $this->manager;
			}
			catch(MongoConnectionException $e)
			{
				throw new EMongoException(Yii::t(
					'yii',
					'EMongoDB failed to open connection: {error}',
					array('{error}'=>$e->getMessage())
				), $e->getCode());
			}
		}
		else
			return $this->manager;
	}

	/**
	 * Set the connection
	 *
	 * @param Mongo $connection
	 * @since v1.0
	 */
	public function setConnection(Mongodb $connection)
	{
		$this->manager = $connection;
        $this->bulk = new MongoDB\Driver\BulkWrite;
	}

	/**
	 * If we have don't use presist connection, close it
	 * @since v1.0
	 */
	public function __destruct(){
        if($this->manager!==null){
            $this->manager = null;
            $this->bulk = null;
            Yii::trace('Closing MongoDB connection', 'ext.MongoDb.EMongoDB');
        }
    }
}
