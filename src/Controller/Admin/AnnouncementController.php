<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Roles;
use App\Form\AnnouncementType;
use App\Form\Model\AnnouncementModel;
use App\Mail\AnnouncementMailer;
use App\Service\AnnouncementManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::DOMAIN_ADMIN)]
final class AnnouncementController extends AbstractController
{
    use DomainFilterTrait;

    public function __construct(
        private readonly AnnouncementManager $manager,
        private readonly AnnouncementMailer $mailer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/announcements/', name: 'admin_announcement_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$domain, $domainId, $selectedDomainName] = $this->resolveDomainFilter($request, $this->em);

        return $this->render('Admin/Announcement/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $domain),
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
        ]);
    }

    #[Route('/admin/announcements/create', name: 'admin_announcement_create', methods: ['GET'])]
    public function create(): Response
    {
        $model = $this->createModel();
        $form = $this->createAnnouncementForm($model);

        return $this->renderCreate($form);
    }

    #[Route('/admin/announcements/create', name: 'admin_announcement_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $model = $this->createModel();
        $form = $this->createAnnouncementForm($model);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderCreate($form);
        }

        $domain = $model->getDomain();
        if (null === $domain) {
            return $this->renderCreate($form);
        }

        $this->denyAccessUnlessGranted('view', $domain);

        $user = $this->getUser();
        $author = $user instanceof User ? $user : null;
        $fingerprint = $model->getFingerprint();
        $footerDomain = $this->isGranted(Roles::ADMIN) ? null : (string) $domain->getName();
        $button = $form instanceof Form ? $form->getClickedButton() : null;

        $clicked = $button instanceof FormInterface ? $button->getName() : null;

        if ('send' === $clicked) {
            $sendToken = (string) $form->get('sendToken')->getData();

            // Only content the admin has seen in the preview may be sent
            if ($form->get('previewFingerprint')->getData() !== $fingerprint || 1 !== preg_match('/^[0-9a-f]{32}$/', $sendToken)) {
                $this->addFlash('error', 'admin.announcement.flash.preview-outdated');
            } else {
                // The token is used once, so a form submitted twice doesn't send twice
                if (null === $this->manager->send($domain, $model->getSubject(), $model->getBody(), $author, $sendToken)) {
                    $this->addFlash('error', 'admin.announcement.flash.already-sent');
                } else {
                    $this->addFlash('success', 'admin.announcement.flash.sent');
                }

                return $this->redirectToRoute('admin_announcement_index');
            }
        }

        if ('test' === $clicked && null !== $author) {
            $this->mailer->send($author->getEmail(), $model->getSubject(), $model->getBody(), $footerDomain);
            $this->addFlash('success', 'admin.announcement.flash.test-sent');
        }

        return $this->renderCreate($form, [
            'domain' => $domain,
            'subject' => $model->getSubject(),
            'body' => $this->mailer->buildBody($model->getBody(), $footerDomain),
            'recipientCount' => $this->manager->countRecipients($domain),
            'fingerprint' => $fingerprint,
            'sendToken' => bin2hex(random_bytes(16)),
        ]);
    }

    private function createModel(): AnnouncementModel
    {
        $model = new AnnouncementModel();
        $user = $this->getUser();

        // Domain admins can only address their own domain
        if ($user instanceof User) {
            $model->setDomain($user->getDomain());
        }

        return $model;
    }

    /**
     * @return FormInterface<AnnouncementModel>
     */
    private function createAnnouncementForm(AnnouncementModel $model): FormInterface
    {
        return $this->createForm(AnnouncementType::class, $model, [
            'action' => $this->generateUrl('admin_announcement_create_post'),
            'method' => 'POST',
            'is_admin' => $this->isGranted(Roles::ADMIN),
        ]);
    }

    /**
     * @param FormInterface<AnnouncementModel> $form
     * @param array<string, mixed>|null        $preview
     */
    private function renderCreate(FormInterface $form, ?array $preview = null): Response
    {
        return $this->render('Admin/Announcement/create.html.twig', [
            'form' => $form,
            'preview' => $preview,
        ]);
    }
}
