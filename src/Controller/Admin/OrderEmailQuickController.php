<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\SimpleOrderMailer;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class OrderEmailQuickController extends AbstractController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrl) {}

    #[Route('/admin/orders/{id}/send-status/{status}', name: 'admin_order_send_status', methods: ['GET'])]
    public function sendStatus(Order $order, string $status, SimpleOrderMailer $mailer): Response
    {
        $allowed = ['paid', 'preparing', 'shipped'];
        if (!in_array($status, $allowed, true)) {
            $this->addFlash('warning', 'Statut non autorisé.');
            return $this->redirectBackToOrder($order);
        }

        try {
            $mailer->sendStatusEmail($order, $status, null);
            $this->addFlash('success', 'E-mail envoyé (' . $status . ').');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Échec de l’envoi : ' . $e->getMessage());
        }

        return $this->redirectBackToOrder($order);
    }

    private function redirectBackToOrder(Order $order): Response
    {
        $url = $this->adminUrl
            ->unsetAll()
            ->setController(OrderCrudController::class)
            ->setAction('detail')
            ->setEntityId($order->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
