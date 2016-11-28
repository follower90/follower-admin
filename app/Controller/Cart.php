<?php

namespace App\Controller;

use Core\Orm;
use Core\Router;

class Cart extends Controller
{
	public function methodIndex()
	{
		if (\App\Service\Cart::getCartCount() == 0) {
			$data['content'] = $this->view->render('templates/cart_empty.phtml');
			return $this->render($data);
		}

		$cart = \App\Service\Cart::getCart()->getData();

		$data = [
			'cart' => $cart,
			'total' => \App\Service\Cart::getTotal(),
			'userinfo' => $this->user ? Orm::findOne('User_Info', ['userId'], $this->user->getId())->getValues() : []
		];

		$data['content'] = $this->view->render('templates/cart.phtml', $data);
		$data['breadcrumbs'] = $this->renderBreadCrumbs([['name' => i18n('Cart')]]);
		return $this->render($data);
	}

	public function methodRemove($args)
	{
		$item = \App\Service\Cart::find($args['remove']);
		if ($item) Orm::delete($item);

		return $this->methodIndex();
	}

	public function methodRecalculate($args)
	{
		$i = 0;
		foreach ($args['id'] as $id) {
			\App\Service\Cart::update($id, $args['count'][$i]);
		}
		return $this->methodIndex();
	}

	public function methodOrder($args)
	{
		$order = \Core\Orm::create('Order');

		$order->setValues([
			'userId' => $this->user ? $this->user->getId() : null,
			'sum' => \App\Service\Cart::getTotal(),
			'firstName' => $args['firstName'],
			'lastName' => $args['lastName'],
			'email' => $args['email'],
			'phone' => $args['phone'],
			'address' => $args['address'],
			'comment' => $args['comment']
		]);

		$order->save();

		$cart = \App\Service\Cart::getCart()->getCollection();

		foreach($cart as $c) {
			$product = Orm::load('Product', $c->getValue('productId'));

			$orderedProduct = \Core\Orm::create('Order_Product');
			$orderedProduct->setValues([
				'orderId' => $order->getId(),
				'productId' => $product->getId(),
				'name' => $product->getValue('name'),
				'price' => $product->getValue('price'),
				'count' => $c->getValue('count')
			]);

			$orderedProduct->save();
		}

		$siteName = \Admin\Object\Setting::get('sitename');
		$userInfo = $this->user->getValues();

		$mailTemplate = \Admin\Object\MailTemplate::get('new_order');
		$body = $this->view->renderInlineTemplate(
			$mailTemplate->getValue('body'),
			[
				'products' => \Core\Orm::find('Order_Product',['orderId'],[$order->getId()])->getData(),
				'order' => $order->getValues(),
				'site' => $siteName,
				'name' => $userInfo['info']['firstName'] .' '. $userInfo['info']['lastName'],
			]
		);

		\App\Service\Mail::send($userInfo['info']['email'], $siteName .' - ' . $mailTemplate->getValue('subject'), $body);

		\App\Service\Cart::clear();
		Router::redirect('/cart/ordersent');
	}

	public function methodOrderSent($args)
	{
		$info = Orm::findOne('InfoBlock', ['alias'], ['order_sent'])->getValues();

		$data['content'] = $this->view->render('templates/page.phtml', $info);
		return $this->render($data);
	}
}
