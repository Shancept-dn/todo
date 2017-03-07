<?php

/**
 * Класс-синглтон для соединения с БД
 * Class Db
 */
class Db {

	/**
	 * Хранит инстанс подключения к БД
	 * @var \Doctrine\ORM\EntityManager
	 */
	private static $_entityManager;

	/**
	 * Создает (при первом вызове) и возвращает инстанс подключения к БД
	 * @param array $config
	 * @return \Doctrine\ORM\EntityManager
	 */
	public static function getEntityManager($config) {
		if(null === self::$_entityManager) {
			$setup = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([$config['models']], $config['isDevMode']);

			$conn = \Doctrine\DBAL\DriverManager::getConnection([
				'url' => $config['url'],
			], (new \Doctrine\DBAL\Configuration()));

			self::$_entityManager = \Doctrine\ORM\EntityManager::create($conn, $setup);
		}

		return self::$_entityManager;
	}

	/**
	 * Конструктор класса
	 */
	private function __construct() {}

}