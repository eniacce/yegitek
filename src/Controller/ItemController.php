<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/items', name: 'item_')]
class ItemController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ItemRepository $itemRepository,
    ) {}

    // LIST
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $items = $this->itemRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('item/index.html.twig', [
            'items' => $items,
        ]);
    }

    // CREATE
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item, [
            'submit_label' => '💾 Kaydet',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->itemRepository->save($item, true);
            $this->addFlash('success', "✅ '{$item->getName()}' eklendi!");
            return $this->redirectToRoute('item_index');
        }

        return $this->render('item/form.html.twig', [
            'form' => $form,
            'title' => 'Yeni Item Ekle',
            'item' => null,
        ]);
    }

    // EDIT
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Item $item): Response
    {
        $form = $this->createForm(ItemType::class, $item, [
            'submit_label' => '✏️ Güncelle',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', "✅ '{$item->getName()}' güncellendi!");
            return $this->redirectToRoute('item_index');
        }

        return $this->render('item/form.html.twig', [
            'form' => $form,
            'title' => 'Düzenle: ' . $item->getName(),
            'item' => $item,
        ]);
    }

    // DELETE
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Item $item): Response
    {
        $name = $item->getName();
        $this->itemRepository->remove($item, true);
        $this->addFlash('danger', "🗑️ '{$name}' silindi.");
        return $this->redirectToRoute('item_index');
    }
}
