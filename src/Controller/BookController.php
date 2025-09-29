<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/', name: 'books_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        $user = $this->getUser();

        $books = $this->isGranted('ROLE_ADMIN')
            ? $bookRepository->findAll()
            : $bookRepository->findBy(['user' => $user]);

        return $this->render('book/index.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/new', name: 'books_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setUser($this->getUser());
            $book->setCreatedAt(new \DateTimeImmutable());
            $book->setUpdatedAt(new \DateTimeImmutable());

            // Image de couverture
            $coverFile = $form->get('coverImage')->getData();
            if ($coverFile) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();
                $coverFile->move(
                    $this->getParameter('covers_directory'),
                    $newFilename
                );
                $book->setCoverImage($newFilename);
            }

            // Slug
            $book->setSlug($slugger->slug($book->getTitle()));

            $em->persist($book);
            $em->flush();

            $this->addFlash('success', 'Livre créé avec succès !');
            return $this->redirectToRoute('books_index');
        }

        return $this->render('book/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}/edit', name: 'books_edit', methods: ['GET','POST'])]
    public function edit(
        string $slug,
        BookRepository $bookRepository,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $book = $bookRepository->findOneBy(['slug' => $slug]);
        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        // Contrôle d’accès
        if (!$this->isGranted('ROLE_ADMIN') && $book->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setUpdatedAt(new \DateTimeImmutable());

            $coverFile = $form->get('coverImage')->getData();
            if ($coverFile) {
                if ($book->getCoverImage()) {
                    $oldPath = $this->getParameter('covers_directory').'/'.$book->getCoverImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();
                $coverFile->move($this->getParameter('covers_directory'), $newFilename);
                $book->setCoverImage($newFilename);
            }

            $book->setSlug($slugger->slug($book->getTitle()));
            $em->flush();

            $this->addFlash('success', 'Livre mis à jour avec succès !');
            return $this->redirectToRoute('books_index');
        }

        return $this->render('book/edit.html.twig', [
            'form' => $form->createView(),
            'book' => $book,
        ]);
    }

    #[Route('/{slug}', name: 'books_show', methods: ['GET'])]
    public function show(string $slug, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->findOneBy(['slug' => $slug]);
        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $book->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('book/show.html.twig', ['book' => $book]);
    }

    #[Route('/{slug}/delete', name: 'books_delete', methods: ['POST'])]
    public function delete(string $slug, BookRepository $bookRepository, Request $request, EntityManagerInterface $em): Response
    {
        $book = $bookRepository->findOneBy(['slug' => $slug]);
        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $book->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            if ($book->getCoverImage()) {
                $path = $this->getParameter('covers_directory').'/'.$book->getCoverImage();
                if (file_exists($path)) unlink($path);
            }

            $em->remove($book);
            $em->flush();
            $this->addFlash('success', 'Livre supprimé avec succès !');
        }

        return $this->redirectToRoute('books_index');
    }
}
