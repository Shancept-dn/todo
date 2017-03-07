<?php

/**
 * Класс-синглтон для обработки HTTP REST запросов
 * Class Request
 */
class Request {

	/**
	 * Хранит инстанс класса
	 * @var Request
	 */
	private static $_instance;

	/**
	 * Хранит метод запроса
	 * @var string
	 */
	private $_method;
	/**
	 * Хранит путь запроса ?_path=... в виде массива (разделитель /)
	 * @var array
	 */
	private $_path;
	/**
	 * Хранит название контроллера (первая часть пути запроса)
	 * @var bool|string
	 */
	private $_controller;
	/**
	 * Хранит название экшена (вторая часть пути запроса)
	 * @var bool|string
	 */
	private $_action;
	/**
	 * Хранит разобранные входные данные REST запроса
	 * @var array
	 */
	private $_input;

	/**
	 * Создает (при первом вызове) и возвращает инстанс класса
	 * @return Request
	 */
	public static function getInstance() {
		if(null === self::$_instance) self::$_instance = new self;

		return self::$_instance;
	}

	/**
	 * Возвращает метод запроса
	 * @return string В верхнем регистре
	 */
	public function getMethod() {
		return strtoupper( $this->_method );
	}

	/**
	 * Возвращает путь запроса из ?_path=...
	 * @return array
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * Возвращает название контроллера
	 * @return string В CamelCase
	 */
	public function getController() {
		return ucfirst(strtolower( $this->_controller ));
	}

	/**
	 * Возвращает название экшена
	 * @return string В CamelCase
	 */
	public function getAction() {
		return ucfirst(strtolower( $this->_action ));
	}

	/**
	 * Возвращает входные данные запроса
	 * @return array
	 */
	public function getInput() {
		return $this->_input;
	}

	/**
	 * Создает инстанс и инициализиует свойства по HTTP-запросу
	 */
	private function __construct() {
		$this->_method = self::calcMethod();
		$this->_path = self::calcPath();
		$this->_controller = self::calcController($this->_path);
		$this->_action = self::calcAction($this->_path);
		$this->_input = self::calcInput($this->_method);
	}

	/**
	 * Возвращает входные данные запроса
	 * @param string $method
	 * @return array
	 */
	private static function calcInput($method) {
		if(isset($_GET['_path'])) unset($_GET['_path']);
		switch($method) {
			case 'POST': return $_POST;
			case 'PUT': return self::getParsedPHPInput();
			case 'DELETE': return self::getParsedPHPInput();
			default: return $_GET;
		}
	}

	/**
	 * Возвращает разобранные данные php://input
	 * @return array
	 */
	private static function getParsedPHPInput() {
		$parsed = [];
		parse_str(file_get_contents('php://input'), $parsed);
		return $parsed;
	}

	/**
	 * Возвращает название экшена
	 * @param string $path путь запроса
	 * @return bool|string
	 */
	private static function calcAction($path) {
		if(isset($path[1])) return $path[1];
		return false;
	}

	/**
	 * Возвращает название контроллера
	 * @param string $path путь запроса
	 * @return bool|string
	 */
	private static function calcController($path) {
		if(isset($path[0])) return $path[0];
		return false;
	}

	/**
	 * Разбирает путь запроса из ?_path=...
	 * @return array
	 */
	private static function calcPath() {
		if(!isset($_GET['_path'])) return [];
		$parts = explode('/', $_GET['_path']);
		$parts = array_map(function($item){
			return trim($item);
		}, $parts);
		$parts = array_filter($parts, function($item){
			return $item !== '';
		});
		return array_values($parts);
	}

	/**
	 * Возвращает название метода запроса
	 * @return string
	 */
	private static function calcMethod() {
		if(isset($_SERVER['REQUEST_METHOD'])) {
			return $_SERVER['REQUEST_METHOD'];
		}
		return 'GET';
	}

}