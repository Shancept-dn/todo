<?php

/**
 * Базовый класс для контроллеров API
 * Class Controller
 */
class Controller {

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

}