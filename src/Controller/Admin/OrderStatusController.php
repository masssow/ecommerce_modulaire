<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
// use App\Service\OrderEmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class OrderStatusController extends AbstractController
{
    #[Route('/admin/orders/{id}/status', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
        AdminUrlGenerator $adminUrlGenerator,
        // ?OrderEmailService $orderEmailService = null
    ) {
        $token = $request->request->get('_token');
        if (!$csrf->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('order_status' . $id, $token))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('ea_index'));
        }

        /** @var Order|null $order */
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('ea_index'));
        }

        $newStatus = (string) $request->request->get('status');
        if (!OrderStatus::isValid($newStatus)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('ea_index'));
        }

        $old = method_exists($order, 'getStatus') ? $order->getStatus() : null;
        if ($old === $newStatus) {
            $this->addFlash('info', 'Aucun changement : statut déjà à jour.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('ea_index'));
        }

        $order->setStatus($newStatus);
        $em->flush();

        // optionnel : envoi auto d'email si un template est lié à ce statut
        // if ($orderEmailService) {
        //     $orderEmailService->sendOnStatusChange($order, $old, $newStatus);
        // }

        $this->addFlash('success', 'Statut mis à jour.');

        // Retour à la page précédente
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        $url = $adminUrlGenerator
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }
}
