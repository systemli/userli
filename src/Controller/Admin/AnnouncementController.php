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
use Symfony\Component\Form\ClickableInterface;
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

        switch ($this->getClickedButtonName($form)) {
            case 'send':
                // Only content the admin has seen in the preview may be sent
                if ($form->get('previewFingerprint')->getData() !== $fingerprint) {
                    $this->addFlash('error', 'admin.announcement.flash.preview-outdated');
                    break;
                }

                $this->manager->send($domain, $model->getSubject(), $model->getBody(), $author);
                $this->addFlash('success', 'admin.announcement.flash.sent');

                return $this->redirectToRoute('admin_announcement_index');
            case 'test':
                if (null !== $author) {
                    $this->mailer->send($author->getEmail(), $model->getSubject(), $model->getBody());
                    $this->addFlash('success', 'admin.announcement.flash.test-sent');
                }

                break;
        }

        return $this->renderCreate($form, [
            'domain' => $domain,
            'subject' => $model->getSubject(),
            'body' => $this->mailer->buildBody($model->getBody()),
            'recipientCount' => $this->manager->countRecipients($domain),
            'fingerprint' => $fingerprint,
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
     */
    private function getClickedButtonName(FormInterface $form): ?string
    {
        foreach (['send', 'test', 'preview'] as $name) {
            $button = $form->get($name);
            if ($button instanceof ClickableInterface && $button->isClicked()) {
                return $name;
            }
        }

        return null;
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
