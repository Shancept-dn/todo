<?php

namespace Controllers;

class Item extends \Controller {

	/**
	 * Хранит модель дела из списка
	 * @var \Models\Item
	 */
	private $item;

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

		//Для всех экшенов кроме add - обязательный входной параметр id
		if($action != 'add') {
			//Получаем id из входных параметров
			$id = $this->checkInputData('id', 'int');

			//Получаем модель по id
			$this->item = $this->findAndCheckItem($id);
		}

		return parent::beforeAction($action, $method);
	}

	/**
	 * Выполняетсмя после успешного выполнения экшена
	 * @param string $action
	 * @param string $method
	 * @return bool
	 */
	public function afterAction($action, $method) {
		//Сбрасываем кэш, связанный с застронутым списком дел
		$rosterId = $this->item->getRoster()->getId();
		\Api::app()->cache->deleteByTag('Roster|'.$rosterId);

		return parent::afterAction($action, $method);
	}

	/**
	 * Добавить дело в список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionAddPOST() {
		//Проверяем входные данные
		$rosterId = $this->checkInputData('roster_id', 'int');
		$text = $this->checkInputData('text');

		$userId = \Api::app()->auth->getUser()->getId();

		//Проверяем права на список дел
		if(!\Api::app()->db->getRepository('Models\Roster')->checkRosterAllowEditing($rosterId, $userId))
			throw new \HttpException(403);

		//Создаем запись в списке
		$this->item = \Api::app()->db->getRepository('Models\Item')->crateItem($rosterId, $text);
		if(!$this->item) throw new \HttpException(500);

		return ['id' => $this->item->get_id()];
	}

	/**
	 * Удалить дело из списка
	 * @return array
	 * @throws \HttpException
	 */
	public function actionDeleteDELETE() {
		//Удаляем запись из списка
		if(!\Api::app()->db->getRepository('Models\Item')->deleteItem($this->item)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Изменить текст дела
	 * @return array
	 * @throws \HttpException
	 */
	public function actionTextPUT() {
		//Проверяем входные данные
		$text = $this->checkInputData('text');

		$this->item->setText($text);

		if(!\Api::app()->db->getRepository('Models\Item')->saveItem($this->item)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Изменить сделано/не сделано делу
	 * @return array
	 * @throws \HttpException
	 */
	public function actionDonePUT() {
		//Проверяем входные данные
		$done = $this->checkInputData('done', 'int', true);

		$this->item->setDone($done);

		if(!\Api::app()->db->getRepository('Models\Item')->saveItem($this->item)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Ищет дело по id и проверяет может ли текущий пользователь его редактировать
	 * @param int $id
	 * @return null|\Models\Item
	 * @throws \HttpException
	 */
	private function findAndCheckItem($id) {
		//Ищем дело по id
		if(null === $item = \Api::app()->db->find('Models\Item', $id)) throw new \HttpException(404);

		//Текущий пользователь
		$userId = \Api::app()->auth->getUser()->getId();

		//Список, которому принадлежит дело
		$roster = $item->getRoster();

		//Проверяем может ли текущий пользователь редактировать список
		if(!\Api::app()->db->getRepository('Models\Roster')->checkRosterAllowEditing($roster, $userId))
			throw new \HttpException(403);

		return $item;
	}
}