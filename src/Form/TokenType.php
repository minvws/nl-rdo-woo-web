<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Dossier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<TokenType>
 */
class TokenType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('remark', TextType::class, [
                'label' => 'Remark',
                'required' => true,
                'help' => 'Informatie over het token, bijvoorbeeld de naam van de gebruiker.',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])
            ->add('dossier', EntityType::class, [
                'label' => 'Dossier',
                'class' => Dossier::class,
                'choice_label' => function (Dossier $dossier) {
                    return 'Dossier ' . $dossier->getDossierNr() . ' - ' . $dossier->getTitle();
                },
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
