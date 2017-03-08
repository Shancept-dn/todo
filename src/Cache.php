<?php

/**
 * Прокси для Memcache с кэшированием/удалением по тегу
 * Class Cache
 */
class Cache {

	/**
	 * Суффик для ключа при сохранении в Memcache
	 */
	const KEY_SUFFIX = 'TODO_KEY_';
	/**
	 * Суффик для тега при сохранении в Memcache
	 */
	const TAG_SUFFIX = 'TODO_TAG_';

	/**
	 * Хранит объект Memcache
	 * @var Memcache
	 */
	public $memcache;

	/**
	 * Хранит инстанс класса
	 * @var Request
	 */
	private static $_instance;

	/**
	 * Создает (при первом вызове) и возвращает инстанс класса
	 * @param array $config
	 * @return Cache
	 */
	public static function getInstance($config) {
		if(null === self::$_instance) self::$_instance = new self($config);

		return self::$_instance;
	}

	/**
	 * Запоминает значение по ключу
	 * @param string $key
	 * @param mixed $value
	 * @param null|string $tag
	 */
	public function set($key, $value, $tag = null) {
		$this->memcache->set(self::KEY_SUFFIX.$key, $value);
		if(null !== $tag) $this->addKeyToTag($tag, $key);
	}

	/**
	 * Возвращает значение по ключу
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->memcache->get(self::KEY_SUFFIX.$key);
	}

	/**
	 * Удаляет запись по ключу
	 * @param string $key
	 * @return bool
	 */
	public function delete($key) {
		return $this->memcache->delete(self::KEY_SUFFIX.$key);
	}

	/**
	 * Удаляет все записи с определенным тегом
	 * @param string $tag
	 */
	public function deleteByTag($tag) {
		$keys = $this->getTagKeys($tag);
		foreach($keys as $key) $this->delete($key);
		$this->memcache->delete(self::TAG_SUFFIX.$tag);
	}

	/**
	 * Возвращает список ключей с определенным тегом
	 * @param string $tag
	 * @return array
	 */
	private function getTagKeys($tag) {
		if($keys = $this->memcache->get(self::TAG_SUFFIX.$tag)) return $keys;
		return [];
	}

	/**
	 * Добавляет ключ в тег
	 * @param string $tag
	 * @param string $key
	 * @return bool
	 */
	private function addKeyToTag($tag, $key) {
		$keys = $this->getTagKeys($tag);
		if(false !== array_search($key, $keys)) return true;
		$keys[] = $key;
		return $this->memcache->set(self::TAG_SUFFIX.$tag, $keys);
	}

	/**
	 * Конструктор класса, создает подключение к Memcache
	 * @param array $config
	 */
	private function __construct($config) {
		$this->memcache = new Memcache();
		$this->memcache->addServer($config['host'], $config['port']);
	}
}