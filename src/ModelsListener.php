<?php

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class ModelsListener {

	private function log($model, $event, $type) {
		error_log(get_class($model).'('.$model->getId().'): '.$type);
	}

	/** @PrePersist */
	public function prePersistHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PrePersist');
	}

	/** @PostPersist */
	public function postPersistHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PostPersist');
	}

	/** @PreUpdate */
	public function preUpdateHandler($model, PreUpdateEventArgs $event) {
		$this->log($model, $event, 'PreUpdate');
	}

	/** @PostUpdate */
	public function postUpdateHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PostUpdate');
	}

	/** @PostRemove */
	public function postRemoveHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PostRemove');
	}

	/** @PreRemove */
	public function preRemoveHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PreRemove');
	}

	/** @PreFlush */
	public function preFlushHandler($model, PreFlushEventArgs $event) {
		$this->log($model, $event, 'PreFlush');
	}

	/** @PostLoad */
	public function postLoadHandler($model, LifecycleEventArgs $event) {
		$this->log($model, $event, 'PostLoad');
	}
}