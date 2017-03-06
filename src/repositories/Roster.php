<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Roster extends EntityRepository {

	/**
	 * Возвращает списки пользоваля с данными о расшаривании
	 * @param int $userId
	 * @return array
	 */
	public function getUserRosters($userId) {
		$dql = 'SELECT r,i,s,su FROM Models\Roster r JOIN r.user u JOIN r.items i JOIN r.shares s JOIN s.user su WHERE u.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $userId)
			->getArrayResult();
	}
}