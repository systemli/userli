<?php

namespace App\Controller;

use App\Creator\VoucherCreator;
use App\Entity\Alias;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Enum\Roles;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Exception\ValidationException;
use App\Form\CustomAliasCreateType;
use App\Form\Model\AliasCreate;
use App\Form\Model\OpenPgpKey as OpenPgpKeyModel;
use App\Form\Model\PasswordChange;
use App\Form\Model\VoucherCreate;
use App\Form\PasswordChangeType;
use App\Form\RandomAliasCreateType;
use App\Form\VoucherCreateType;
use App\Form\OpenPgpKeyType;
use App\Handler\AliasHandler;
use App\Handler\MailCryptKeyHandler;
use App\Handler\WkdHandler;
use App\Handler\VoucherHandler;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StartController.
 */
class StartController extends AbstractController
{
    /**
     * @var AliasHandler
     */
    private $aliasHandler;
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var VoucherHandler
     */
    private $voucherHandler;
    /**
     * @var VoucherCreator
     */
    private $voucherCreator;
    /**
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var WkdHandler
     */
    private $wkdHandler;

    /**
     * StartController constructor.
     */
    public function __construct(AliasHandler $aliasHandler,
                                PasswordUpdater $passwordUpdater,
                                VoucherHandler $voucherHandler,
                                VoucherCreator $voucherCreator,
                                MailCryptKeyHandler $mailCryptKeyHandler,
                                ObjectManager $manager,
                                WkdHandler $wkdHandler)
    {
        $this->aliasHandler = $aliasHandler;
        $this->passwordUpdater = $passwordUpdater;
        $this->voucherHandler = $voucherHandler;
        $this->voucherCreator = $voucherCreator;
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->manager = $manager;
        $this->wkdHandler = $wkdHandler;
    }

    /**
     * @return Response
     */
    public function indexAction()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // forward to installer if no domains exist
            if (0 == $this->manager->getRepository('App:Domain')->count([])) {
                return $this->redirectToRoute('init');
            }

            return $this->render('Start/index_anonymous.html.twig');
        }

        if ($this->isGranted(Roles::SPAM)) {
            return $this->render('Start/index_spam.html.twig');
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'Start/index.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
            ]
        );
    }

    /**
     * @return Response
     */
    public function voucherAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $voucherCreateForm = $this->createForm(
            VoucherCreateType::class,
            new VoucherCreate(),
            [
                'action' => $this->generateUrl('vouchers'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $voucherCreateForm->handleRequest($request);

            if ($voucherCreateForm->isSubmitted() && $voucherCreateForm->isValid()) {
                $this->createVoucher($request, $user);
            }
        }

        $vouchers = $this->voucherHandler->getVouchersByUser($user);

        return $this->render(
            'Start/vouchers.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'vouchers' => $vouchers,
                'voucher_form' => $voucherCreateForm->createView(),
            ]
        );
    }

    /**
     * @return Response
     */
    public function aliasAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $randomAliasCreateForm = $this->createForm(
            RandomAliasCreateType::class,
            new AliasCreate(),
            [
                'action' => $this->generateUrl('aliases'),
                'method' => 'post',
            ]
        );

        $aliasCreate = new AliasCreate();
        $customAliasCreateForm = $this->createForm(
            CustomAliasCreateType::class,
            $aliasCreate,
            [
                'action' => $this->generateUrl('aliases'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $randomAliasCreateForm->handleRequest($request);
            $customAliasCreateForm->handleRequest($request);

            if ($randomAliasCreateForm->isSubmitted() && $randomAliasCreateForm->isValid()) {
                $this->createRandomAlias($request, $user);
            } elseif ($customAliasCreateForm->isSubmitted() && $customAliasCreateForm->isValid()) {
                $this->createCustomAlias($request, $user, $aliasCreate->alias);
            }
        }

        $aliasRepository = $this->get('doctrine')->getRepository('App:Alias');
        $aliasesRandom = $aliasRepository->findByUser($user, true);
        $aliasesCustom = $aliasRepository->findByUser($user, false);

        return $this->render(
            'Start/aliases.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'alias_creation_random' => $this->aliasHandler->checkAliasLimit($aliasesRandom, true),
                'alias_creation_custom' => $this->aliasHandler->checkAliasLimit($aliasesCustom),
                'aliases_custom' => $aliasesCustom,
                'aliases_random' => $aliasesRandom,
                'random_alias_form' => $randomAliasCreateForm->createView(),
                'custom_alias_form' => $customAliasCreateForm->createView(),
            ]
        );
    }

    /**
     * @return Response
     */
    public function accountAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $passwordChange = new PasswordChange();
        $passwordChangeForm = $this->createForm(
            PasswordChangeType::class,
            $passwordChange,
            [
                'action' => $this->generateUrl('account'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $passwordChangeForm->handleRequest($request);

            if ($passwordChangeForm->isSubmitted() && $passwordChangeForm->isValid()) {
                $this->changePassword($request, $user, $passwordChange->getPlainPassword(), $passwordChange->password);
            }
        }

        return $this->render(
            'Start/account.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'password_form' => $passwordChangeForm->createView(),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
            ]
        );
    }

    /**
     * @return Response
     */
    public function openPgpAction(Request $request): ?Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $openPgp = new OpenPgpKeyModel();
        $openPgpKeyForm = $this->createForm(
            OpenPgpKeyType::class,
            $openPgp,
            [
                'action' => $this->generateUrl('openpgp'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $openPgpKeyForm->handleRequest($request);

            if ($openPgpKeyForm->isSubmitted() && $openPgpKeyForm->isValid()) {
                /** @var UploadedFile $keyFile */
                $keyFile = $openPgpKeyForm->get('keyFile')->getData();
                /** @var string $keyText */
                $keyText = $openPgpKeyForm->get('keyText')->getData();

                if ($keyFile) {
                    $content = file_get_contents($keyFile->getPathname());
                    $openPgpKey = $this->importOpenPgpKey($request, $user, $content);
                } elseif ($keyText) {
                    $openPgpKey = $this->importOpenPgpKey($request, $user, $keyText);
                }
            }
        }

        if (!isset($openPgpKey) || null === $openPgpKey->getKeyId()) {
            $openPgpKey = $this->wkdHandler->getKey($user);
        }

        return $this->render(
            'Start/openpgp.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'openpgp_form' => $openPgpKeyForm->createView(),
                'openpgp_id' => $openPgpKey->getKeyId(),
                'openpgp_fingerprint' => $openPgpKey->getKeyFingerprint(),
                'openpgp_expiretime' => $openPgpKey->getKeyExpireTime(),
            ]
        );
    }

    private function createVoucher(Request $request, User $user)
    {
        if ($this->isGranted('ROLE_MULTIPLIER')) {
            try {
                $this->voucherCreator->create($user);

                $request->getSession()->getFlashBag()->add('success', 'flashes.voucher-creation-successful');
            } catch (ValidationException $e) {
                // Should not thrown
            }
        }
    }

    private function createRandomAlias(Request $request, User $user)
    {
        try {
            if ($this->aliasHandler->create($user) instanceof Alias) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }
    }

    private function createCustomAlias(Request $request, User $user, string $alias)
    {
        try {
            if ($this->aliasHandler->create($user, $alias) instanceof Alias) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function changePassword(Request $request, User $user, string $newPassword, string $oldPassword)
    {
        $user->setPlainPassword($newPassword);
        $this->passwordUpdater->updatePassword($user);
        // Reencrypt the MailCrypt key with new password
        if ($user->hasMailCryptSecretBox()) {
            $this->mailCryptKeyHandler->update($user, $oldPassword);
        }
        $user->eraseCredentials();

        $this->getDoctrine()->getManager()->flush();

        $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');
    }

    private function importOpenPgpKey(Request $request, User $user, string $key): OpenPgpKey
    {
        $openPgpKey = new OpenPgpKey();
        try {
            $openPgpKey = $this->wkdHandler->importKey($key, $user);
            $request->getSession()->getFlashBag()->add('success', 'flashes.openpgp-key-upload-successful');
        } catch (NoGpgDataException $e) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-no-openpgp');
        } catch (NoGpgKeyForUserException $e) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-no-keys');
        } catch (MultipleGpgKeysForUserException $e) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-multiple-keys');
        }

        return $openPgpKey;
    }
}
