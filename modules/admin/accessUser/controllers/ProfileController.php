<?php

namespace accessUser\controllers;

use accessUser\models\form\ChangePassword;
use accessUser\models\form\ChangeUserEmail;
use accessUser\models\User;
use accessUser\models\UserProfile;
use app\classes\AuthFilter;
use app\classes\UploadImage;
use app\components\Controller;
use Yii;
use yii\filters\VerbFilter;

/**
 * Profile controller
 */
class ProfileController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'auth'  => [
                'class' => AuthFilter::className(),
                'only'  => ['change-password', 'update-profile', 'update-user-email'],
            ],
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'upload-photo' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $model = UserProfile::findOne(Yii::$app->user->id);
        if ($model) {
            return $this->render('index', [
                'model' => $model,
            ]);
        }

        return $this->redirect(['update-profile']);
    }

    public function actionUpdateProfile()
    {
        $model = UserProfile::findOne(Yii::$app->user->id);
        $model = $model ?: new UserProfile([
            'id' => Yii::$app->user->id,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $transaction->commit();
                    $this->flash('success', 'Update profile success');
                    return $this->redirect(['index']);
                }
            } catch (\Exception $exc) {
                $model->addError('', $exc->getMessage());
                $this->flash('error', $exc->getMessage());
            }
            $transaction->rollBack();
        }

        return $this->render('update-profile', [
            'model' => $model,
        ]);
    }

    public function actionUpdateUserEmail()
    {
        $user  = Yii::$app->user->identity;
        $model = new ChangeUserEmail([
            'username' => $user->username,
            'email'    => $user->email,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->change()) {
            $this->flash('success', 'Update account login success');
            return $this->redirect(['index']);
        }

        return $this->render('update-user-email', [
            'model' => $model,
        ]);
    }

    public function actionChangePassword()
    {
        $model = new ChangePassword();
        if ($model->load(Yii::$app->request->post()) && $model->change()) {
            $this->flash('success', 'Update password success');
            return $this->goHome();
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    public function actionUploadPhoto()
    {
        $model    = UserProfile::findOne(Yii::$app->user->id);
        $photo_id = UploadImage::store('image', [
            'width'  => 400,
            'height' => 400,
        ]);

        if ($photo_id !== false) {
            $model->photo_id = $photo_id;
            $model->save();

            $this->flash('success', 'Update image success');
        }

        return $this->redirect(['index']);
    }
}
