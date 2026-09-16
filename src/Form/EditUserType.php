<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class EditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'disabled' => true,
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le prénom contient moins de {{ limit }} caractères.',
                        'max' => 30,
                        'maxMessage' => 'Le prénom contient plus de {{ limit }} caractères.',
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez renseigner un prénom.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\-\s]+$/u',
                        'message' => 'Ce champ ne peut contenir que des lettres, des espaces et des tirets.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'John',
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom contient moins de {{ limit }} caractères.',
                        'max' => 30,
                        'maxMessage' => 'Le nom contient plus de {{ limit }} caractères.',
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez renseigner un nom.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\-\s]+$/u',
                        'message' => 'Ce champ ne peut contenir que des lettres, des espaces et des tirets.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Doe',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'COMPTA' => 'COMPTA',
                    'COLLAB' => 'COLLAB',
                    'CLIENT' => 'CLIENT',
                ],
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn-alice-form',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
