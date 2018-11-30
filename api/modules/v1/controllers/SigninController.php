<?php

//namespace frontend\modules\user\controllers;
namespace frontendnew\modules\user\controllers;

use common\models\User;
use frontend\modules\user\models\LoginForm;
use frontend\modules\user\models\PasswordResetRequestForm;
use frontend\modules\user\models\ResetPasswordForm;
use frontend\modules\user\models\SignupForm;
use Yii;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter; 
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use common\models\Utility;
use yii\widgets\ActiveForm;
use yii\helpers\Security;

//require_once 'C:\xampp\htdocs\nm\vendor\swiftmailer\swiftmailer\lib\swift_required.php';

class SigninController extends \yii\web\Controller {

	public $enableCsrfValidation = false;

	public function actions() {
		return [
			'oauth' => [
				'class' => 'yii\authclient\AuthAction',
				'successCallback' => [$this, 'successOAuthCallback'],
			]
		];
	}

	public function successCallback($client) {
		$attributes = $client->getUserAttributes();
// user login or signup comes here
	}

	public function behaviors() {
		return [
			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'actions' => ['signup', 'registration', 'mobileregister', 'mobilelogin', 'checkuser', 'checkemail', 'login', 'request-password-reset', 'reset-password', 'oauth', 'verify', 'forgot'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'actions' => ['signup', 'checkuser', 'login', 'request-password-reset', 'reset-password', 'oauth'],
						'allow' => false,
						'roles' => ['@'],
						'denyCallback' => function() {
					return Yii::$app->controller->redirect(['/user/default/profile']);
				}
					],
					[
						'actions' => ['logout', 'updateprofile', 'checkemail'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
//                'actions' => [
//                    'logout' => ['post'],
//                ],
			],
		];
	}

	public function actionLogin() {

		$model = new LoginForm();
		if (!empty($_POST['LoginForm'])) {
			$model->attributes = $_POST['LoginForm'];

			if ($model->login()) {
				if (!empty($_POST['page_url']) && $_POST['page_url'] == 'verify') {
					return 'verify';
				} else {
					return 'success';
				}
			} else {
				$error = $model->getErrors();
				$message = '<div class="alert alert-danger">' . $error['password'][0] . '</div>';
				return $message;
			}
		}
	}

	public function actionVerify($email, $auth_key) {
		$this->layout = '../../../../views/layouts/full_col';
		$languageModel = new \frontend\models\Language;
		$langlist = $languageModel->getLanguages();
		$menuItems = $languageModel->getMenuItems("en");
		Yii::$app->view->params['langlist'] = $langlist;
		Yii::$app->view->params['lang'] = "en";
		Yii::$app->view->params['menuItems'] = $menuItems;
		Yii::$app->view->params['LanguageUrl'] = "";

		$error = '';
		$model = User::find()->where(['user_auth_key' => $auth_key])->one();


		if (empty($model)) {
			$error = '<div class="alert alert-danger"> Unable to verify the authentication link, please try again. </div>';
			$model = !empty($model) ? $model : new User();
		}
		if (!empty($_POST)) {
			$model->user_password_hash = $_POST['User']['new_password_hash'];
			$model->user_updated_by = $model->user_name;
			$model->setPassword($model->user_password_hash);

			if ($model->save()) {
				$error = '<div class="alert alert-success">Password changed successfully</div>';
			}
		}

		$model->new_password_hash = '';
		$model->confirm_password_hash = '';

		return $this->render('resetPassword', [
					'model' => $model,
					'error' => $error,
		]);
	}

	public function actionLogout() {
		$Utility = new Utility();
		Yii::$app->user->logout();
		$isMobile = $Utility->isMobileDetect();
		if ($isMobile) {
			return $this->redirect(BASE_URL . 'news');
		} else {
			return $this->redirect(BASE_URL);
		}
	}

	public function actionCheckuser() {
		$user_name = $_POST['user_name'];

		$user = User::find()
				->where(['user_name' => $user_name])
				->count();
		if ($user > 0) {

			return json_encode(array('valid' => false));
		} else {
			return json_encode(array('valid' => true));
		}
	}

	public function actionCheckemail() {
		$user_email = $_POST['email'];
		$user_id = !empty(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : "";
		if (empty($user_id)) {
			$user = User::find()
					->where(['user_email' => $user_email, 'user_access' => 'email'])
					->count();
		} else {
//            echo "select * from user where user_email = '$user_email' and user_id NOT IN ($user_id)";die;
			$user = User::findBySql("select * from user where user_email = '$user_email' and user_id NOT IN ($user_id)")->count();
		}
		if ($user > 0) {

			return json_encode(array('valid' => false));
		} else {
			return json_encode(array('valid' => true));
		}
	}

	public function actionSignup() {
		$model = new SignupForm();
		$apiModel = new \frontend\models\Api();

		$user = new User();

		$user->scenario = "sign_up";
		$create = true;
		if (!empty($_POST)) {
			$count = $user->find()->Where(['user_email' => $_POST['email']])->count();
			if ($count < 1) {
				if (!empty($_POST['first_name']) && !empty($_POST['last_name'])) {
					$user->user_name_display = htmlspecialchars($_POST['first_name'] . ' ' . $_POST['last_name']);
				}
				if (!empty($_POST['email'])) {
					$user->user_email = $_POST['email'];
					$user->user_name = htmlspecialchars($_POST['email']);
				}
				if (!empty($_POST['user_title'])) {
					$user->user_title = htmlspecialchars($_POST['user_title']);
				} else {
					$user->user_title = "Mr";
				}

				if (!empty($_POST['user_password'])) {
					$user->user_password_hash = htmlspecialchars($_POST['user_password']);
					$user->setPassword($user->user_password_hash);
				}

				if (!empty($_POST['country_code']) && !empty($_POST['phone_number'])) {
					$user->user_contact_no = $_POST['country_code'] . ' ' . $_POST['phone_number'];
				} else if (!empty($_POST['phone_number'])) {
					$user->user_contact_no = $_POST['phone_number'];
				}


				if (!empty($_POST['user_profession'])) {
					$user->user_profession = htmlspecialchars($_POST['user_profession']);
				}
				if (!empty($_POST['user_area_of_interest'])) {
					$user->user_area_of_interest = htmlspecialchars($_POST['user_area_of_interest']);
				}

				if (!empty($_POST['user_date_of_birth'])) {
					$user->user_date_of_birth = date('Y-m-d', strtotime($_POST['user_date_of_birth']));
				}
				date_default_timezone_set('Asia/Kolkata');
				$currentDate1 = date("Y-m-d H:i:s");
				$user->user_created_at = $currentDate1;
				$user->user_updated_on = $currentDate1;
				$user->user_status = 'enabled';
				$user->user_access = 'email';
				$user->user_role = 'web';
				$user->confirm_password = $user->user_password_hash;
				$user->generateAuthKey($user->user_auth_key);

				if ($user->save()) {
					$first_name = !empty($_POST['first_name']) ? $_POST['first_name'] : "";
					$last_name = !empty($_POST['last_name']) ? $_POST['last_name'] : "";
					$url = sprintf(SUBSCRIBE_API, $user->user_email, $user->user_title, $first_name, $last_name, $_POST['phone_number'], urlencode(htmlspecialchars($user->user_profession)), urlencode(htmlspecialchars($user->user_area_of_interest)), date('Y/m/d', strtotime($_POST['user_date_of_birth'])), $first_name);
					$response = file_get_contents($url);
					$apiModel->errorLog('SUBSCRIBE_API' . '-Web' . '-' . $user->user_email, $response . "\n" . $url, 'web', '4a812cb27256b9320921bd8a64e35c1d');
					$logins = User::find()->where(['or', ['user_id' => $user->user_id]])->one();
					if (Yii::$app->user->login($logins)) {
						return "success";
					}
				}
			}
		}
//return $this->redirect(BASE_URL, '302');
	}

	public function actionUpdateprofile() {


		$model = new SignupForm();
		$apiModel = new \frontend\models\Api;
		$user = User::find()->Where(['user_id' => Yii::$app->user->identity->user_id])->one();


		if (!empty($_POST)) {

			if (!empty($_POST['first_name']) && !empty($_POST['last_name'])) {
				$user->user_name_display = htmlspecialchars($_POST['first_name'] . ' ' . $_POST['last_name']);
			}

			$user->user_email = $_POST['email'];
			if ($user->user_access == "email") {
				$user->user_name = htmlspecialchars($_POST['email']);
			}
			if (!empty($_POST['user_title'])) {
				$user->user_title = htmlspecialchars($_POST['user_title']);
			}

//                if (!empty($_POST['user_password'])) {
//                    $user->user_password_hash = $_POST['user_password'];
//                    //$user->setPassword($user->user_password_hash);
//                }

			if (!empty($_POST['country_code']) && !empty($_POST['phone_number'])) {
				$user->user_contact_no = $_POST['country_code'] . ' ' . $_POST['phone_number'];
			}

			if (!empty($_POST['user_profession'])) {
				$user->user_profession = htmlspecialchars($_POST['user_profession']);
			}
			if (!empty($_POST['user_area_of_interest'])) {
				$user->user_area_of_interest = htmlspecialchars($_POST['user_area_of_interest']);
			}

			if (!empty($_POST['user_date_of_birth'])) {
				$user->user_date_of_birth = date('Y-m-d', strtotime($_POST['user_date_of_birth']));
			}
			date_default_timezone_set('Asia/Kolkata');
			$currentDate1 = date("Y-m-d H:i:s");
			$user->user_created_at = $currentDate1;
			$user->user_updated_on = $currentDate1;
			$user->user_status = 'enabled';


//$user->confirm_password = $user->user_password_hash;
//                 echo'<pre>';
//        print_r($user->attributes);die;
//$user->generateAuthKey($user->user_auth_key);
			if ($user->save()) {
//				if ($user->user_access == 'twitter' || $user->user_access == 'facebook') {
//					$url = sprintf(SUBSCRIBE_API, $user->user_email, $user->user_title, $_POST['first_name'], $_POST['last_name'], $_POST['phone_number'], urlencode(htmlspecialchars($user->user_profession)), urlencode(htmlspecialchars($user->user_area_of_interest)), date('Y/m/d', strtotime($_POST['user_date_of_birth'])), $_POST['first_name']);
//					$url = sprintf(UPDATE_SUBSCRIBE_API, $user->user_email, $user->user_title, $_POST['first_name'], $_POST['last_name'], $_POST['phone_number'], urlencode(htmlspecialchars($user->user_profession)), urlencode(htmlspecialchars($user->user_area_of_interest)), date('Y/m/d', strtotime($_POST['user_date_of_birth'])), $_POST['first_name']);
//					$response = file_get_contents($url);
//					$apiModel->errorLog('SUBSCRIBE_API' . '-Web' . '-' . $user->user_email, $response . "\n" . $url, 'web', '4a812cb27256b9320921bd8a64e35c1d');
//				} else {
//					$url = sprintf(UPDATE_SUBSCRIBE_API, $user->user_email, $user->user_title, $_POST['first_name'], $_POST['last_name'], $_POST['phone_number'], urlencode(htmlspecialchars($user->user_profession)), urlencode(htmlspecialchars($user->user_area_of_interest)), date('Y/m/d', strtotime($_POST['user_date_of_birth'])), $_POST['first_name']);
//					$response = file_get_contents($url);
//					$apiModel->errorLog('UPDATE_SUBSCRIBE_API' . '-Web' . '-' . $user->user_email, $response . "\n" . $url, 'web', '4a812cb27256b9320921bd8a64e35c1d');
//				}
				$logins = User::find()->where(['or', ['user_id' => $user->user_id]])->one();
				if (Yii::$app->user->login($logins)) {
					return "success";
				}
			}
		}
//return $this->redirect(BASE_URL, '302');
	}

	public function actionRequestPasswordReset() {
		$model = new PasswordResetRequestForm();
		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->sendEmail()) {
				Yii::$app->getSession()->setFlash('alert', [
					'body' => Yii::t('frontend', 'Check your email for further instructions.'),
					'options' => ['class' => 'alert-success']
				]);

				return $this->goHome();
			} else {
				Yii::$app->getSession()->setFlash('alert', [
					'body' => Yii::t('frontend', 'Sorry, we are unable to reset password for email provided.'),
					'options' => ['class' => 'alert-error']
				]);
			}
		}

		return $this->render('requestPasswordResetToken', [
					'model' => $model,
		]);
	}

	public function actionResetPassword($token) {
		try {
			$model = new ResetPasswordForm($token);
		} catch (InvalidParamException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}

		if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
			Yii::$app->getSession()->setFlash('alert', [
				'body' => Yii::t('frontend', 'New password was saved.'),
				'options' => ['class' => 'alert-success']
			]);
			return $this->goHome();
		}

		return $this->render('resetPassword', [
					'model' => $model,
		]);
	}

	/**
	 * @param \yii\authclient\BaseClient $client
	 */
	public function successOAuthCallback($client) {
// use BaseClient::normalizeUserAttributeMap to provide consistency for user attribute`s names
		$user = new User();
		$attributes = $client->getUserAttributes();
//        echo '<pre>';
//        print_r($attributes);
//        die;
		if ($client->getName() == 'facebook') {
			$clientAccess = "facebook";
			$username = empty($attributes['email']) ? $attributes['id'] : $attributes['email'];
			$user = User::find()->where(['user_access' => $clientAccess, 'user_name' => $username])->one();
			if (!$user) {
				$user = new User();
				$user->scenario = 'oauth_create';
				$user->user_name = $username;
				$user->user_email = !empty($attributes['email']) ? $attributes['email'] : null;
				$user->user_name_display = $attributes['name'];
				$user->user_access = $clientAccess;
				$user->user_created_at = new \yii\db\Expression('now()');
				$user->user_access = $clientAccess;
				$user->user_role = 'web';
				$user->user_status = 'enabled';
				$user->user_created_at = new \yii\db\Expression('now()');
				$password = Yii::$app->security->generateRandomString(8);
				$user->setPassword($password);
				$user->save();
				Yii::$app->getUser()->login($user);
				Yii::$app->setHomeUrl(BASE_URL . "subscribe");
				return true;
			}
			if (Yii::$app->getUser()->login($user)) {

				return true;
			} else {
				throw new Exception('OAuth error');
			}
		}
		if ($client->getName() == 'twitter') {
			$clientAccess = "twitter";
			$user = User::find()->where(['user_access' => $clientAccess, 'user_name' => $attributes['screen_name']])->one();
			if (!$user) {
				$user = new User();
				$user->scenario = 'oauth_create';
				$user->user_name = $attributes['screen_name'];
//$user->user_email = $attributes['screen_name'];
				$user->user_name_display = $attributes['name'];
				$user->user_access = $clientAccess;
				$user->user_role = 'web';
				$user->user_status = 'enabled';
				$user->user_created_at = new \yii\db\Expression('now()');
				$password = Yii::$app->security->generateRandomString(8);
				$user->setPassword($password);
				$user->save();
				Yii::$app->getUser()->login($user);
				Yii::$app->setHomeUrl(BASE_URL . "subscribe");
				return true;
			}
			if (Yii::$app->getUser()->login($user)) {
				return true;
			} else {
				throw new Exception('OAuth error');
			}
		}
	}

	public function actionForgot($email) {
		header("Access-Control-Allow-Origin: *");
		$user = User::find()->where(['user_email' => $email, 'user_access' => 'email'])->one();

		if (!empty($user)) {
			$auth_key = str_replace(" ", "-", $user->user_name_display . '-' . microtime());
			$user->user_auth_key = strtolower($auth_key);
			$user->update();
			$mailmodel = array(
				'user_name' => $user->user_name_display,
				'user_email' => $user->user_email,
				'user_auth' => $user->user_auth_key,
				'base_url' => BASE_URL
			);

			$message = 'Dear ' . $user->user_name_display . ',<br>

                                <p>Thank you for registering on www.narendramodi.in.<br>
                                
                                <p>We are introducing special features for registered members of www.narendramodi.in.<br>
                                
                                <p>You can get personalised birthday greetings from the Prime Minister, regular news & updates on email as well as mailers according to your profession and area of interest.<br>
                                
                                <p>Click the link below, reset your password and complete the registration details and enjoy the exclusive features.<br><p>'
					. $mailmodel['base_url'] . 'user/verify?email=' . $mailmodel['user_email'] . '&auth_key=' . $mailmodel ['user_auth'] . '</p><br>' .
					'<p>Regards,<br>
                                Admin: www.narendramodi.in</p>';


			$mail = new \yii\web\PHPMailer();
//                    $mail->IsSMTP();
			$mail->SMTPAuth = false;
			$mail->Host = SMTP_HOST;
			$mail->Port = SMTP_PORT;
			$mail->SMTPSecure = '';
			$mail->Username = SMTP_USER;
			$mail->Password = "";
			$mail->SetFrom(SMTP_USER, 'Narendramodi.in');
			$mail->Subject = "Please Reset your password";
			$mail->MsgHTML($message);
			$mail->AddAddress($user->user_email, $user->user_name_display);

			if ($mail->Send()) {
				$message = '<div class="alert alert-success">
                                <p>A mail has been sent to you, please click on the link and reset your password.</p> 
                            </div>';

				return $message;
			}
		} else {
			$message = '<div class="alert alert-success">
                                <p>Sorry, this Email id does not exist in our database.</p>
                                </div>';

			return $message;
		}
	}

	public function actionMobilelogin() {
		if (empty($lang)) {
			$lang = "en";
			$langurl = "";
		} else {
			$langurl = $lang . "/";
		}
		Yii::$app->view->params['pageName'] = 'Login';
		Yii::$app->view->params['LanguageUrl'] = $langurl;
		Yii::$app->view->params['req_url'] = Yii::$app->request->url;
		$this->layout = '../../../../viewsmobile/layouts/main.php';

		return $this->render('mobilelogin');
	}

	public function actionMobileregister() {
		if (empty($lang)) {
			$lang = "en";
			$langurl = "";
		} else {
			$langurl = $lang . "/";
		}
		Yii::$app->view->params['pageName'] = 'Register';
		Yii::$app->view->params['LanguageUrl'] = $langurl;
		Yii::$app->view->params['req_url'] = Yii::$app->request->url;
		$this->layout = '../../../../viewsmobile/layouts/main.php';

		return $this->render('mobileregister');
	}

}
