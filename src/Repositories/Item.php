<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Item extends EntityRepository {

	/**
	 * Возвращает данные спика
	 * @param int $rosterId
	 * @return array
	 */
	public function getItems($rosterId) {
		$dql = 'SELECT i FROM Models\Item i JOIN i.roster r WHERE r.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $rosterId)
			->getArrayResult();
	}
}