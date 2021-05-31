<?php

declare(strict_types=1);

use SallePW\SlimApp\Controller\ChangePasswordController;
use SallePW\SlimApp\Controller\FriendsController;
use SallePW\SlimApp\Controller\LogOutController;
use SallePW\SlimApp\Controller\ProfileController;
use SallePW\SlimApp\Controller\RegisterController;
use SallePW\SlimApp\Controller\LogInController;
use SallePW\SlimApp\Controller\LandingController;
use SallePW\SlimApp\Controller\WalletController;

use SallePW\SlimApp\Controller\StoreController;
use SallePW\SlimApp\Controller\VerifyUserController;
use SallePW\SlimApp\Controller\WishListController;
use SallePW\SlimApp\Middleware\StartSessionMiddleware;
use SallePW\SlimApp\Middleware\VerifySessionMiddleware;

$app->add(StartSessionMiddleware::class);

$app->get(
    '/login',
    LogInController::class . ":show"
)->setName('login');

$app->post(
    '/login',
    LogInController::class . ":handleFormSubmission"
)->setName('handle-login');

$app->get(
    '/register',
    RegisterController::class . ":show"
)->setName('register');

$app->post(
    '/register',
    RegisterController::class . ":handleFormSubmission"
)->setName('handle-register');

$app->get(
    '/activate',
    VerifyUserController::class . ":verifyUser"
)->setName('verify');

$app->post(
    '/logOut',
    LogOutController::class . ":handle_log_out"
)->setName('logOut');

$app->get(
    '/profile',
    ProfileController::class . ":show"
)->setName('profile')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->post(
    '/profile',
    ProfileController::class . ":handleUpdate"
)->setName('profileUpdate')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/',
    LandingController::class . ":show"
)->setName('home');

$app->get(
    '/store',
    StoreController::class . ":show"
)->setName('store');

$app->post(
    '/store/buy/{gameId}',
    StoreController::class . ":buy"
)->setName('handle-store-buy')->add($app->getContainer()->get('verifySessionMiddleware'));;

$app->get(
    '/profile/changePassword',
    ChangePasswordController::class . ":show"
)->setName('changePassword')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->post(
    '/profile/changePassword',
    ChangePasswordController::class . ":handleUpdate"
)->setName('changePasswordUpdate')->add($app->getContainer()->get('verifySessionMiddleware'));;

$app->get(
    '/user/myGames',
    StoreController::class . ":myGames"
)->setName('myGames')->add($app->getContainer()->get('verifySessionMiddleware'));;

$app->get(
    '/user/wallet',
    WalletController::class . ":show"
)->setName('getWallet')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->post(
    '/user/wallet',
    WalletController::class . ":handleUpdate"
)->setName('postWallet')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/friends',
    FriendsController::class . ":show"
)->setName('friends')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/friendRequests',
    FriendsController::class . ":showRequests"
)->setName('friendRequests')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/friendRequests/send',
    FriendsController::class . ":showRequestCreation"
)->setName('sendRequest')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->post(
    '/user/friendRequests/send',
    FriendsController::class . ":handleSendRequest"
)->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/friendRequests/accept/{requestId}',
    FriendsController::class . ":acceptRequest"
)->setName('acceptFriendRequest')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/friendRequests/decline/{requestId}',
    FriendsController::class . ":declineRequest"
)->setName('declineFriendRequest')->add($app->getContainer()->get('verifySessionMiddleware'));


$app->get(
    '/user/wishlist',
    WishListController::class . ":show"
)->setName('wishlist')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->get(
    '/user/wishlist/{gameId}',
    WishListController::class . ":showSingleGame"
)->setName('wishlist')->add($app->getContainer()->get('verifySessionMiddleware'));


$app->post(
    '/user/wishlist/{gameId}',
    WishListController::class . ":addWish"
)->setName('handle-wishlist-add-wish')->add($app->getContainer()->get('verifySessionMiddleware'));

$app->delete(
    '/user/wishlist/{gameId}',
    WishListController::class . ":deleteWish"
)->setName('handle-wishlist-delete-wish')->add($app->getContainer()->get('verifySessionMiddleware'));
