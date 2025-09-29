<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private SluggerInterface $slugger;

    public function __construct(UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger)
    {
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        // --- Create Admin User ---
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // --- Create Regular User ---
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        // --- Create Book 1 ---
        $book1 = new Book();
        $book1->setTitle('The Hobbit');
        $book1->setAuthor('J.R.R. Tolkien');
        $book1->setDescription('Bilbo Baggins embarks on an unexpected adventure.');
        $book1->setGenre('Fantasy');
        $book1->setUser($user);
        $book1->setCoverImage('placeholder.jpg');
        $book1->setCreatedAt(new \DateTimeImmutable());
        $book1->setUpdatedAt(new \DateTimeImmutable());
        $book1->setSlug($this->slugger->slug($book1->getTitle()));
        $manager->persist($book1);

        // --- Create Book 2 ---
        $book2 = new Book();
        $book2->setTitle('1984');
        $book2->setAuthor('George Orwell');
        $book2->setDescription('A dystopian novel about totalitarian regime.');
        $book2->setGenre('Science Fiction');
        $book2->setUser($user);
        $book2->setCoverImage('placeholder.jpg');
        $book2->setCreatedAt(new \DateTimeImmutable());
        $book2->setUpdatedAt(new \DateTimeImmutable());
        $book2->setSlug($this->slugger->slug($book2->getTitle()));
        $manager->persist($book2);

        $manager->flush();
    }
}
