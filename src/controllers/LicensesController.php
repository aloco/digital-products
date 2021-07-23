<?php

namespace craft\digitalproducts\controllers;

use Craft;
use craft\digitalproducts\elements\License;
use craft\digitalproducts\elements\Product;
use craft\elements\User;
use craft\web\Controller as BaseController;
use yii\base\Exception;
use yii\web\Response;

/**
 * Class DigitalProducts_LicensesController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class LicensesController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->requirePermission('digitalProducts-manageLicenses');

        parent::init();
    }

    /**
     * Create or edit a License
     *
     * @param int|null $licenseId the license id
     * @param License|null $license the license
     * @return Response
     */
    public function actionEdit(int $licenseId = null, License $license = null): Response
    {
        if ($license === null) {
            if ($licenseId === null) {
                $license = new License();
            } else {
                $license = Craft::$app->getElements()->getElementById($licenseId, License::class);

                if (!$license) {
                    $license = new License();
                }
            }
        }

        $variables['title'] = $license->id ? (string)$license : Craft::t('digital-products', 'Create a new License');
        $variables['license'] = $license;
        $variables['userElementType'] = User::class;
        $variables['productElementType'] = Product::class;

        return $this->renderTemplate('digital-products/licenses/_edit', $variables);
    }

    /**
     * Save a License.
     *
     * @return Response|null
     * @throws Exception if a non existing license id provided
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $licenseId = $request->getBodyParam('licenseId');

        if ($licenseId) {
            /** @var License $license */
            $license = Craft::$app->getElements()->getElementById($licenseId, License::class);

            if (!$license) {
                throw new Exception('No license with the ID “{id}”', ['id' => $licenseId]);
            }
        } else {
            $license = new License();
        }

        $productIds = $request->getBodyParam('product');
        $userIds = $request->getBodyParam('owner');

        if (is_array($productIds) && !empty($productIds)) {
            $license->productId = reset($productIds);
        }

        if (is_array($userIds) && !empty($userIds)) {
            $license->userId = reset($userIds);
        }

        $license->id = $request->getBodyParam('licenseId');
        $license->enabled = (bool)$request->getBodyParam('enabled');
        $license->ownerName = $request->getBodyParam('ownerName');
        $license->ownerEmail = $request->getBodyParam('ownerEmail');

        // Save it
        if (!Craft::$app->getElements()->saveElement($license)) {
            Craft::$app->getSession()->setError(Craft::t('digital-products', 'Couldn’t save license.'));
            Craft::$app->getUrlManager()->setRouteParams(['license' => $license]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('digital-products', 'License saved.'));
        return $this->redirectToPostedUrl($license);
    }

    /**
     * Delete a License.
     *
     * @return Response|null
     * @throws Exception if a non existing license id provided
     */
    public function actionDelete()
    {
        $this->requirePostRequest();

        $licenseId = Craft::$app->getRequest()->getRequiredBodyParam('licenseId');
        /** @var License $license */
        $license = Craft::$app->getElements()->getElementById($licenseId, License::class);

        if (!$license) {
            throw new Exception('No license with the ID “{id}”', ['id' => $licenseId]);
        }

        if (Craft::$app->getElements()->deleteElement($license)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('digital-products', 'License deleted.'));
            return $this->redirectToPostedUrl($license);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => false]);
        }

        Craft::$app->getSession()->setError(Craft::t('digital-products', 'Couldn’t delete license.'));
        Craft::$app->getUrlManager()->setRouteParams(['license' => $license]);

        return null;
    }
}
