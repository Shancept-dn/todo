<?php

namespace Controllers;

class User extends \Controller {

	public function actionFooGET() {
		return ['foo' => 'bar'];
	}

	public function actionFooPUT() {}

	public function actionBarPOST() {}

	public function actionBaz() {}

}