<?php

namespace App\Controller\Admin;

use App\Entity\EmailLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class EmailLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log e-mail')
            ->setEntityLabelInPlural('Logs e-mails')
            ->setPageTitle(Crud::PAGE_INDEX, 'Historique des e-mails')
            ->setDefaultSort(['sentAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['recipient', 'subject', 'errorMessage', 'template.name']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('success', 'Succès'))
            ->add(TextFilter::new('recipient', 'Destinataire'))
            ->add(TextFilter::new('subject', 'Sujet'))
            ->add(EntityFilter::new('template', 'Modèle'))
            ->add(EntityFilter::new('orderRef', 'Commande'))
            ->add(DateTimeFilter::new('sentAt', 'Envoyé le'));
    }

    public function configureFields(string $pageName): iterable
    {
        $id         = IdField::new('id', 'ID')->onlyOnIndex();
        $order      = AssociationField::new('orderRef', 'Commande')
            ->setCrudController(OrderCrudController::class);
        $template   = AssociationField::new('template', 'Modèle')
            ->setCrudController(EmailTemplateCrudController::class);
        $recipient  = TextField::new('recipient', 'Destinataire');
        $subject    = TextField::new('subject', 'Sujet');
        $success    = BooleanField::new('success', 'Envoyé');
        $sentAt     = DateTimeField::new('sentAt', 'Envoyé le')
            ->setFormat('dd/MM/yyyy HH:mm');
        $error      = TextEditorField::new('errorMessage', 'Erreur')->onlyOnDetail();
        $bodyDetail = TextEditorField::new('bodyHtml', 'Contenu HTML')
        ->setTemplatePath('admin/fields/field_html_preview.html.twig')
            ->onlyOnDetail();

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $sentAt, $success, $recipient, $subject, $order, $template];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$sentAt, $success, $recipient, $subject, $order, $template, $bodyDetail, $error];
        }

        // Pas d'édition/création manuelle de logs dans l’admin
        return [$sentAt, $success, $recipient, $subject, $order, $template, $bodyDetail, $error];
    }

    public function configureActions(Actions $actions): Actions
    {
        // Lien “Envoyer à nouveau” -> ouvre le formulaire d’envoi pour la commande
        $resend = Action::new('resend', 'Envoyer à nouveau', 'fa fa-paper-plane')
            ->displayIf(fn(EmailLog $log) => null !== $log->getOrderRef())
            ->linkToRoute('admin_order_send_email', function (EmailLog $log) {
                $params = ['id' => $log->getOrderRef()->getId()];
                if ($log->getTemplate()) {
                    $params['tpl'] = $log->getTemplate()->getId();
                }
                return $params;
            });

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resend)
            ->add(Crud::PAGE_DETAIL, $resend);
    }
}
