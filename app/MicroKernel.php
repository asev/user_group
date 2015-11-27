<?php

use AppBundle\Entity\Group;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Routing\Route;

class MicroKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new AppBundle\AppBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->addRoute((new Route('/', ['_controller' => 'kernel:indexAction'])));

        //todo: Implement admin role restriction for the following routes

        $routes->addRoute((new Route('/user', ['_controller' => 'kernel:createUserAction']))->setMethods(['POST']));
        $routes->addRoute((new Route('/user/{username}', ['_controller' => 'kernel:createUserAction']))->setMethods(['PUT']));
        $routes->addRoute((new Route('/user/{username}', ['_controller' => 'kernel:deleteUserAction']))->setMethods(['DELETE']));

        $routes->addRoute((new Route('/group', ['_controller' => 'kernel:createGroupAction']))->setMethods(['POST']));
        $routes->addRoute((new Route('/group/{id}', ['_controller' => 'kernel:deleteGroupAction']))->setMethods(['DELETE']));

        $routes->addRoute((new Route('/user-group', ['_controller' => 'kernel:createUserGroupAction']))->setMethods(['POST']));
        $routes->addRoute((new Route('/user-group/{username}/{groupId}', ['_controller' => 'kernel:createUserGroupAction']))->setMethods(['PUT']));
        $routes->addRoute((new Route('/user-group/{username}/{groupId}', ['_controller' => 'kernel:deleteUserGroupAction']))->setMethods(['DELETE']));
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }

    /**
     * @return Response
     */
    public function indexAction()
    {
        return new Response('user_group status: OK');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createUserAction(Request $request)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        //todo: Add some validation

        $username = $request->get('username');
        if ($username === null) {
            return new JsonResponse(['success' => 0, 'message' => 'username is missing'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $entityManager->getRepository('AppBundle:User')
            ->find($username);

        if ($user !== null) {
            return new JsonResponse(['success' => 0, 'message' => 'user already exist'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User($username);
        $name = $request->get('name');
        if ($name !== null) {
            $user->setName($name);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'user has been created']);
    }

    /**
     * @param string $username
     *
     * @return JsonResponse
     */
    public function deleteUserAction($username)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user = $entityManager->getRepository('AppBundle:User')
            ->find($username);

        if ($user === null) {
            return new JsonResponse(['success' => 0, 'message' => 'user not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'user has been deleted']);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createGroupAction(Request $request)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        //todo: Add some validation

        $name = $request->get('name');
        if ($name === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group name is missing'], Response::HTTP_BAD_REQUEST);
        }

        $group = new Group();
        $group->setName($name);

        $entityManager->persist($group);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'group has been created']);
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function deleteGroupAction($id)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var Group $group */
        $group = $entityManager->getRepository('AppBundle:Group')
            ->find($id);

        if ($group === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$group->getUsers()->isEmpty()) {
            return new JsonResponse(['success' => 0, 'message' => 'group still has assigned users'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($group);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'group has been deleted']);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createUserGroupAction(Request $request)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $username = $request->get('username');
        if ($username === null) {
            return new JsonResponse(['success' => 0, 'message' => 'username is missing'], Response::HTTP_BAD_REQUEST);
        }

        $groupId = $request->get('groupId');
        if ($groupId === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group id is missing'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $entityManager->getRepository('AppBundle:User')
            ->find($username);

        if ($user === null) {
            return new JsonResponse(['success' => 0, 'message' => 'user not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var Group $group */
        $group = $entityManager->getRepository('AppBundle:Group')
            ->find($groupId);

        if ($group === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group not found'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getGroups()->contains($group)) {
            return new JsonResponse(['success' => 0, 'message' => 'user is in this group already'], Response::HTTP_BAD_REQUEST);
        }

        $user->addGroup($group);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'user has been assigned to group']);
    }

    /**
     * @param string $username
     * @param int $groupId
     *
     * @return JsonResponse
     */
    public function deleteUserGroupAction($username, $groupId)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($username === null) {
            return new JsonResponse(['success' => 0, 'message' => 'username is missing'], Response::HTTP_BAD_REQUEST);
        }

        if ($groupId === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group id is missing'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $entityManager->getRepository('AppBundle:User')
            ->find($username);

        if ($user === null) {
            return new JsonResponse(['success' => 0, 'message' => 'user not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var Group $group */
        $group = $entityManager->getRepository('AppBundle:Group')
            ->find($groupId);

        if ($group === null) {
            return new JsonResponse(['success' => 0, 'message' => 'group not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->getGroups()->contains($group)) {
            return new JsonResponse(['success' => 0, 'message' => 'user is not assigned to this group'], Response::HTTP_BAD_REQUEST);
        }

        $user->removeGroup($group);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['success' => 1, 'message' => 'user has been removed from the group']);
    }
}
