<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Share extends EntityRepository {

	/**
	 * Возвращает какие списки пользователя кому расшарены
	 * @param int $userId
	 * @return array
	 */
	public function getUserShares($userId) {
		$dql = 'SELECT s,r,su FROM Models\Share s JOIN s.roster r JOIN r.user u JOIN s.user su WHERE u.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $userId)
			->getArrayResult();
	}
}