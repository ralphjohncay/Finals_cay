<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Orders;
use App\Entity\Users;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Mobile/web API orders: bind customer to the logged-in user and recalculate totals (same rules as admin).
 */
final class OrdersProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Orders && $operation instanceof Post) {
            $user = $this->security->getUser();
            if ($user instanceof Users && !$this->security->isGranted('ROLE_ADMIN')) {
                $data->setCustomer($user);
            }

            if (!$data->getOrderDate()) {
                $data->setOrderDate(new \DateTime());
            }

            if (!$data->getStatus()) {
                $data->setStatus('pending_approval');
            }

            $data->recalculateTotal();
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
