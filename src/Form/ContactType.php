<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contact;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
                        'pattern' => '/^[\p{L}0-9\s\'-]+$/u',
                        'message' => 'Le champ ne doit contenir que des lettres, des chiffres, des espaces, des apostrophes et des tirets.',
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
                        'pattern' => '/^[\p{L}0-9\s\'-]+$/u',
                        'message' => 'Le champ ne doit contenir que des lettres, des chiffres, des espaces, des apostrophes et des tirets.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Doe',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Entrez votre adresse email',
                ],
                'constraints' => [
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Votre email contient moins de {{ limit }} caractères.',
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
            ])
            ->add('phone', PhoneNumberType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'default_region' => 'FR',
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                    'maxlength' => '17',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner un numéro de téléphone.',
                    ]),
                    new PhoneNumber([
                        'message' => 'Numéro de téléphone invalide.',
                    ]),
                ],
            ])
            ->add('position', TextType::class, [
                'label' => 'Fonction de l\'interlocuteur',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner une fonction.',
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}0-9_\-\s\'-]+$/u',
                        'message' => 'Le champ ne doit contenir que des lettres, des chiffres, des espaces, des apostrophes, des tirets et des underscores.',
                    ]),
                ],
            ])
            ->add('isMain', CheckboxType::class, [
                'label' => 'Interlocuteur principal',
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
            'data_class' => Contact::class,
        ]);
    }
}
