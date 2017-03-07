<?php

/**
 * Класс-синглтон для Basic-аутентификации пользователя
 * Class Auth
 */
class Auth {

	/**
	 * Хранит инстанс класса
	 * @var Auth
	 */
	private static $_instance;

	/**
	 * Хранит результат аутентификации
	 * @var bool
	 */
	private $_success;
	/**
	 * Хранит модель аутентифицированного пользователя
	 * @var \Models\User
	 */
	private $_user;

	/**
	 * Создает (при первом вызове) и возвращает инстанс класса
	 * @return Auth
	 */
	public static function getInstance() {
		if(null === self::$_instance) self::$_instance = new self;

		return self::$_instance;
	}

	/**
	 * Возвращает результат авторизации
	 * @return bool
	 */
	public function isSuccess() {
		return $this->_success;
	}

	/**
	 * Возвращает модель аутентифицированного пользователя
	 * @return \Models\User
	 */
	public function getUser() {
		return $this->_user;
	}

	/**
	 * Проверяет пароль по хешу
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	public function verifyPassword($password, $hash) {
		return password_verify($password, $hash);
	}

	/**
	 * Хеширует пароль
	 * @param string $password
	 * @return string
	 */
	public function hashPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * Конструктор класса
	 * Проводит Basic-аутентификацию пользователя
	 */
	private function __construct() {
		//Если передали логин и пароль
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$this->_success = false;
			$login = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];

			//Пробуем найти пользователя по логину
			if(null !== $user = Api::app()->db->getRepository('Models\User')->getUserByLogin($login)) {
				//Проверяем переданный пароль
				if($this->verifyPassword($password, $user->getPassword())) {
					$this->_success = true;
					$this->_user = $user;
				}
			}
		}
	}

}