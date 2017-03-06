<?php

/**
 * Основной класс приложения. Синглтон
 * Class Api
 */
class Api {

	/**
	 * Хранит инстанс класса
	 * @var Api
	 */
	private static $_instance;

	/**
	 * Хранит конфигурацию приложения
	 * @var array
	 */
	private $_config;
	/**
	 * Хранит headers, которые нужно отправить при рендеринге ответа
	 * @var array
	 */
	private $_headers = [];

	/**
	 * Создает (при первом вызове) и возвращает инстанс класса
	 * @param array|null $config
	 * @return Api
	 */
	public static function app($config = null) {
		if(null === self::$_instance) self::$_instance = new self($config);

		return self::$_instance;
	}

	/**
	 * Запускает WEB-приложение
	 * 1. По входным данным выбирает контроллер/экшен
	 * 2. Выполняет, результат в JSON
	 * 3. Рендерит данные
	 */
	public function run() {
		/**
		 * Получаем название контроллера/экшена, метод запроса и входные данные
		 */
		$controllerName = $this->request->getController();
		$actionName = $this->request->getAction();
		$methodName = $this->request->getMethod();
		$inputData = $this->request->getInput();

		//Формируем имя класса контроллера в виде "Controllers\{контроллер}"
		$className = 'Controllers\\'.$controllerName;

		//Если класс существует - создаем инстанс, иначе берем стандартный класс Controller
		if(!class_exists($className)) {
			$controller = new Controller;
		} else {
			$controller = new $className;
		}

		//1. check action{actionName}{methodName}
		//2. check action{actionName}
		//3. check action{methodName}
		//4. check actionIndex

		//Список методов для поиска в инстансе
		$methods = [
			'action'.$actionName.$methodName, 	//action{экшен}{метод}
			'action'.$actionName,				//action{экшен}
			'action'.$methodName,				//action{метод}
			'actionIndex',						//actionIndex
		];
		//Если метод OPTIONS - убираем "action{экшен}", т.к. он будет обрабатываться action{экшен}OPTIONS или actionOPTIONS
		if($methodName == 'OPTIONS') unset($methods[1]);

		$httpCode = 200;
		$httpMessage = 'OK';
		$jsonData = null;
		try {
			//Ищем методы в классе
			foreach($methods as $method) {
				if(!is_callable([$controller, $method])) continue;
				$jsonData = $controller->$method($inputData);
				break;
			}
		} catch (HttpException $e) { //В случае HTTP-исключения формируем ответ ошибки
			$httpCode = $e->getCode();
			$httpMessage = $e->getMessage();
			$jsonData = [
				'code' => $httpCode,
				'message' => $httpMessage,
			];
		}

		//Рендерим ответ в виде JSON
		$this->render($jsonData, $httpCode, $httpMessage);
	}

	/**
	 * Добавить header при гендеринге ответа
	 * @param string $header
	 */
	public function addHeader($header) {
		$this->_headers[] = $header;
	}

	/**
	 * $this->request - возвращает инстанс класса Request
	 * $this->db - возвращает инстанс подключения к БД
	 * @param string $name
	 * @return Request|\Doctrine\ORM\EntityManager|null
	 */
	public function __get($name) {
		switch ($name) {
			case 'request': return Request::getInstance();
			case 'db': return Db::getEntityManager($this->_config['db']);
		}
		return null;
	}

	/**
	 * Api constructor.
	 * @param array $config
	 */
	private function __construct($config) {
		$this->_config = $config;
	}

	/**
	 * Рендерит ответ в JSON
	 * @param array|null|bool $jsonData массив данных JSON
	 * @param int $httpCode
	 * @param string $httpMessage
	 */
	private function render($jsonData, $httpCode = 200, $httpMessage = 'OK') {
		header('HTTP/1.1 '.$httpCode.( false !== $httpMessage ? ' '.$httpMessage: '' ));

		//Отправляем дополнительные headers
		foreach($this->_headers as $header) header($header);

		//Если есть JSON-данные  - кодируем в JSON-строку и выводим
		if($jsonData) {
			header('Content-type: application/json; charset=utf8');
			echo json_encode($jsonData);
		}
	}
}