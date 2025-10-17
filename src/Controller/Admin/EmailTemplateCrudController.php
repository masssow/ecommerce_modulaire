<?php

namespace App\Controller\Admin;

use App\Entity\EmailTemplate;
use App\Enum\OrderStatus;
use App\Enum\EmailSubjectPresets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EmailTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle d’e-mail')
            ->setEntityLabelInPlural('Modèles d’e-mails')
            ->setSearchFields(['name', 'subject', 'contentHtml'])
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // 1) Champ "Sujet prédéfini" (non mappé) qui remplit "subject"
        $subjectPreset = ChoiceField::new('subjectPreset', 'Sujet prédéfini')
            ->setChoices(EmailSubjectPresets::formChoices())
            ->setHelp('Choisir un sujet type pour pré-remplir "Objet". Tu peux ensuite l’adapter librement.')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            // astuce JS safe : on copie la valeur choisie vers le champ "subject"
            ->setFormTypeOption('attr', [
                'onchange' => 'const f=this.closest("form"); if(!f) return; const s=f.querySelector(\'input[name$="[subject]"]\'); if(s){ s.value=this.value; }'
            ])
            ->renderAsNativeWidget();

        // 2) Liste déroulante pour relatedStatus (avec tes statuts)
        $relatedStatus = ChoiceField::new('relatedStatus', 'Statut lié (auto)')
            ->setChoices(OrderStatus::formChoices())
            ->setHelp('Optionnel : si défini, ce modèle sera utilisé en envoi automatique quand la commande passe à ce statut.')
            ->allowMultipleChoices(false)
            ->renderAsNativeWidget()
            ->onlyOnForms();

        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Nom'),
            // le preset apparaît avant pour aider, puis le champ libre "subject"
            $subjectPreset,
            TextField::new('subject', 'Objet'),

            TextEditorField::new('contentHtml', 'Contenu HTML')
                ->setHelp('Placeholders Twig utilisables : {{ customer_name }}, {{ order_number }}, {{ order_total }}, {{ order_status }}, {{ tracking_number }}, {{ created_at|date("d/m/Y H:i") }}…'),

            BooleanField::new('isActive', 'Actif'),

            // Upload du logo (Vich)
            TextareaField::new('logoFile', 'Logo (upload)')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setHelp('Dans le HTML, affiche-le avec <img src="cid:logo_cid" alt="Logo">'),

            // Statut auto
            $relatedStatus,
        ];
    }
}
