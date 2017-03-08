<?php

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class ModelsListener {

	/** @PrePersist */
	public function prePersistHandler($model, LifecycleEventArgs $event) {}

	/** @PostPersist */
	public function postPersistHandler($model, LifecycleEventArgs $event) {}

	/** @PreUpdate */
	public function preUpdateHandler($model, PreUpdateEventArgs $event) {}

	/** @PostUpdate */
	public function postUpdateHandler($model, LifecycleEventArgs $event) {}

	/** @PostRemove */
	public function postRemoveHandler($model, LifecycleEventArgs $event) {}

	/** @PreRemove */
	public function preRemoveHandler($model, LifecycleEventArgs $event) {}

	/** @PreFlush */
	public function preFlushHandler($model, PreFlushEventArgs $event) {}

	/** @PostLoad */
	public function postLoadHandler($model, LifecycleEventArgs $event) {}
}