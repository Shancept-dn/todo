<?php

namespace Controllers;

class Roster extends \Controller {

	/**
	 * Выполняется перед всеми экшенами
	 * @param string $action
	 * @param string $method
	 * @return bool
	 * @throws \HttpException
	 */
	public function beforeAction($action, $method) {
		//Для всех эшенов этого контроллера необходима аутентификация
		if(!\Api::app()->auth->isSuccess())  throw new \HttpException(401);

		return parent::beforeAction($action, $method);
	}

	/**
	 * Переименовывает список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionRenamePUT() {
		//Проверяем входные данные
		$id = $this->checkInputData('id', 'int');
		$name = $this->checkInputData('name');

		//Получаем список
		$roster = $this->findAndCheckRoster($id);

		//Переименовываем список
		if(!\Api::app()->db->getRepository('Models\Roster')->renameRoster($roster, $name)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Удаляет список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionDeleteDELETE() {
		//Проверм входные данные
		$id = $this->checkInputData('id', 'int');

		//Получаем список
		$roster = $this->findAndCheckRoster($id);

		//Удаляем список
		if(!\Api::app()->db->getRepository('Models\Roster')->deleteRoster($roster)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Создает новый список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionCreatePOST() {
		//Проверм входные данные
		$name = $this->checkInputData('name');

		//Создаем список
		$rosterId = \Api::app()->db->getRepository('Models\Roster')->createRoster(\Api::app()->auth->getUser(), $name);

		return ['id' => $rosterId];
	}

	/**
	 * Ищет список по id и проверяет принадлежит ли он текущему пользователю
	 * @param int $id
	 * @return null|\Models\Roster
	 * @throws \HttpException
	 */
	private function findAndCheckRoster($id) {
		//Ищем список по id
		if(null === $roster = \Api::app()->db->find('Models\Roster', $id)) throw new \HttpException(404);

		//Проверяем принадлежит ли список авторизованному пользователю
		if(\Api::app()->auth->getUser()->getId() !== $roster->getUser()->getId()) throw new \HttpException(403);

		return $roster;
	}

}