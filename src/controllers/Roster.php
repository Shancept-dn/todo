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
	 * Возвращает информацию о списке (можно ли редактировать, кому расшарен, список дел)
	 * @return array
	 * @throws \HttpException
	 */
	public function actionInfoGET() {
		//Проверяем входные данные
		$id = $this->checkInputData('id', 'int');

		//Получаем список
		$roster = $this->findAndCheckRoster($id, true);

		//Текущий аутентифицированный пользователь
		$userId = \Api::app()->auth->getUser()->getId();

		//Проверяем есть ли информация в кэше
		if(false !== $info = \Api::app()->cache->get('roster/info|'.$userId.'|'.$roster->getId())) return $info;

		//Основная информация
		$info = [
			'id' => $roster->getId(),
			'name' => $roster->getName(),
			'is_mine' => ($roster->getUser()->getId() === $userId),
			'items' => \Api::app()->db->getRepository('Models\Item')->getItems($roster->getId()),
		];

		//Если это собственный список - собираем информацию о том кому расшарен
		if($info['is_mine']) {
			$info['shares'] = [];
			foreach($roster->getShares() as $share) {
				$info['shares'][] = [
					'id' => $share->getUser()->getId(),
					'login' => $share->getUser()->getLogin(),
					'readonly' => $share->getReadonly(),
				];
			}
		} else { //Иначе - можно ли редактировать список
			$info['readonly'] = !\Api::app()->db->getRepository('Models\Roster')->checkRosterAllowEditing($roster, $userId);
		}

		//Сохраняем данные в кэше
		\Api::app()->cache->set(
			'roster/info|'.$userId.'|'.$roster->getId(),
			$info,
			'Roster|'.$roster->getId()
		);

		return $info;
	}

	/**
	 * Возвращает списки, доступные пользователю (собственные и расшаренные)
	 * @return array
	 * @throws \HttpException
	 */
	public function actionListGET() {
		$userId = \Api::app()->auth->getUser()->getId();

		return \Api::app()->db->getRepository('Models\Roster')->getUserAllowedRosters($userId);
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

		//Сбрасываем кэш, связанный с застронутым списком
		\Api::app()->cache->deleteByTag('Roster|'.$roster->getId());

		return ['result' => 'success'];
	}

	/**
	 * Удаляет список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionDeleteDELETE() {
		//Проверяем входные данные
		$id = $this->checkInputData('id', 'int');

		//Получаем список
		$roster = $this->findAndCheckRoster($id);

		//Сбрасываем кэш, связанный с застронутым списком
		\Api::app()->cache->deleteByTag('Roster|'.$roster->getId());

		//Удаляем список
		if(!\Api::app()->db->getRepository('Models\Roster')->deleteRoster($roster)) throw new \HttpException(500);

		return ['result' => 'success'];
	}

	/**
	 * Расшарить (PUT) или отшарить (DELETE) список пользователю
	 * @return array
	 * @throws \HttpException
	 */
	public function actionShare() {
		//Проверяем входные данные
		$id = $this->checkInputData('id', 'int');
		$userId = $this->checkInputData('user_id', 'int');
		$readonly = $this->checkInputData('readonly', 'int', true);

		//Получаем список
		$roster = $this->findAndCheckRoster($id);

		//Проверяем принадлежит ли список текущему пользователю
		if($roster->getUser()->getId() !== \Api::app()->auth->getUser()->getId()) throw new \HttpException(403);

		//Ищем пользователя, которому хотим расшарить список
		if(null === $user = \Api::app()->db->find('Models\User', $userId)) throw new \HttpException(400);

		//Проверяем расшарен ли уже этот список переданному пользователю
		$shared = false;
		foreach($roster->getShares() as $share) {
			if($share->getUser()->getId() !== $user->getId()) continue;
			$shared = $share;
			break;
		}

		//Request метод
		$method = \Api::app()->request->getMethod();

		//Если PUT запрос - расшарить список
		if($method == 'PUT') {
			if(false === $shared) {
				\Api::app()->db->getRepository('Models\Share')->addShare($roster, $user, $readonly);
			} else {
				$shared->setReadonly($readonly);
				\Api::app()->db->getRepository('Models\Share')->saveShare($shared);
			}
			return ['result' => 'success'];
		}
		//DELETE - "отшарить" список
		if($method == 'DELETE') {
			if(false !== $shared) {
				\Api::app()->db->getRepository('Models\Share')->deleteShare($shared);
			}
			return ['result' => 'success'];
		}

		//Сбрасываем кэш, связанный с застронутым списком
		\Api::app()->cache->deleteByTag('Roster|'.$roster->getId());

		throw new \HttpException(400);
	}

	/**
	 * Создает новый список
	 * @return array
	 * @throws \HttpException
	 */
	public function actionCreatePOST() {
		//Проверяем входные данные
		$name = $this->checkInputData('name');

		//Создаем список
		$rosterId = \Api::app()->db->getRepository('Models\Roster')->createRoster(\Api::app()->auth->getUser(), $name);

		return ['id' => $rosterId];
	}

	/**
	 * Ищет список по id и проверяет может ли текущий пользователь его редактировать (читать)
	 * @param int $id
	 * @param bool $checkReadonly проверить у списка только права на чтение
	 * @return null|\Models\Roster
	 * @throws \HttpException
	 */
	private function findAndCheckRoster($id, $checkReadonly = false) {
		//Ищем список по id
		if(null === $roster = \Api::app()->db->find('Models\Roster', $id)) throw new \HttpException(404);

		$userId = \Api::app()->auth->getUser()->getId();

		if($checkReadonly) {
			//Проверяет может ли текущий пользователь читать список
			if(!\Api::app()->db->getRepository('Models\Roster')->checkRosterAllowReading($roster, $userId))
				throw new \HttpException(403);
		} else {
			//Проверяет может ли текущий пользователь редактировать список
			if(!\Api::app()->db->getRepository('Models\Roster')->checkRosterAllowEditing($roster, $userId))
				throw new \HttpException(403);
		}

		return $roster;
	}

}