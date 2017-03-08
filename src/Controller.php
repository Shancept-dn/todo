<?php

/**
 * Базовый класс для контроллеров API
 * Class Controller
 */
class Controller {

	/**
	 * Хранит массив входных данных из Request
	 * @var array
	 */
	protected $input;

	/**
	 * Конструктор класса
	 * @param array $input входные данные из Request
	 */
	public function __construct($input) {
		$this->input = $input;
	}

	/**
	 * Выполняется перед всеми экшенами
	 * @param string $action
	 * @param string $method
	 * @return bool
	 */
	public function beforeAction($action, $method) {
		return true;
	}

	/**
	 * Выполняется после экшена
	 * @param string $action
	 * @param string $method
	 * @return bool
	 */
	public function afterAction($action, $method) {
		return true;
	}

	/**
	 * Отображает 404 ошбку
	 * Вызывается как action по умолчанию
	 * @throws HttpException
	 */
	public function actionIndex() {
		throw new HttpException(404);
	}

	/**
	 * Исходя из названий action`ов дочернего класса (или этого) собирает доступные методы запросов
	 * Результат отображается в header "Allow: ..."
	 * Вызывается по умолчанию для запросов OPTIONS
	 */
	public function actionOPTIONS() {
		$allMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

		$allow = ['OPTIONS'];

		$actionName = Api::app()->request->getAction();

		$actions = get_class_methods($this);

		//Перебираем доступные методы класса
		foreach($actions as $action) {
			//Берем только "action..." методы
			if(0 !== strpos($action, 'action')) continue;
			//Название метода без "action"
			$name = substr($action, 6);
			//Если запрос вида {controller}/{action} и метод класса не "action{action}" - идем дальше
			if($actionName && 0 !== strpos($name, $actionName)) continue;

			//Если название метода совпадает с {action} - доступны все виды запросов
			if($name == $actionName) {
				$allow = $allMethods;
				break;
			} else {
				//Берем вид запроса из названия метода "action{action}{method}"
				$method = substr($name, strlen($actionName));
				//Если этот метод есть в списке возможных - добавляем
				if(false !== array_search($method, $allMethods))
					$allow[] = $method;
			}
		}

		$allow = array_unique($allow);

		//Добавляем header "Allow: ..."
		Api::app()->addHeader('Allow: '.implode(',', $allow));
	}

	/**
	 * Проверят и возвращает значение из входных Request данных
	 * @param string $param
	 * @param string $type int|string|array|undefined
	 * @param bool $allowEmpty может быть пустым
	 * @param bool $optional параметр может быть не передан
	 * @return array|int|string
	 * @throws HttpException
	 */
	protected function checkInputData($param, $type = 'string', $allowEmpty = false, $optional = false) {
		if((!is_array($this->input) || !isset($this->input[$param])) && !$optional)
			throw new \HttpException(400);

		$originalValue = $this->input[$param];
		switch ($type) {
			case 'int': $value = (int)$originalValue; break;
			case 'string': $value = (string)$originalValue; break;
			case 'array': $value = (array)$originalValue; break;
			default: $value = $originalValue;
		}

		if(!$allowEmpty && !$value) throw new \HttpException(400);

		return $value;
	}

}