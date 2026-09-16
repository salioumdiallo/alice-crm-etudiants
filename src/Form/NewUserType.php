<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class NewUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Entrez votre adresse email',
                ],
                'constraints' => [
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Votre email contient moins de 5 caractères.',
                        'maxMessage' => 'Votre email est trop long.',
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre adresse email.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,4}$/',
                        'message' => 'L\'adresse email "{{ value }}" n\'est pas valide.',
                    ]),
                ],
                'invalid_message' => 'Veuillez saisir une adresse mail valide.',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Entrez un mot de passe provisoire',
                ],
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères.',
                        'maxMessage' => 'Le mot de passe doit contenir moins de 20 caractères.',
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez renseigner un mot de passe.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,20}$/',
                        'message' => 'Votre mot de passe doit contenir 1 majuscule, 1 minuscule, 1 caractère spécial, 1 chiffre et être composé de 8 à 20 caractères.',
                    ]),
                ],
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
                    'class' => 'btn-alice btn-alice-form',
                    'data-callback' => 'onSubmit',
                    'data-action' => 'submit',
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
