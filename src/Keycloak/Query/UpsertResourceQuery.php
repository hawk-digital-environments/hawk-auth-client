<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\FailedToWriteResourceException;
use Hawk\AuthClient\Exception\ResourceAlreadyExistsException;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use Hawk\AuthClient\Util\ErrorResponseMessage;

class UpsertResourceQuery
{
    protected ResourceBuilder $builder;

    public function __construct(
        ResourceBuilder $builder
    )
    {
        $this->builder = $builder;
    }

    public function execute(ClientInterface $client): void
    {
        if ($this->builder->doesUpdateExistingResource()) {
            $this->updateExistingResource($client);
        } else {
            $this->createNewResource($client);
        }
    }

    protected function createNewResource(ClientInterface $client): bool
    {
        try {
            $response = $client->request(
                'POST',
                'realms/{realm}/authz/protection/resource_set',
                [
                    'json' => $this->builder->jsonSerialize()
                ]
            );
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                throw new FailedRequestDueToMissingPermissionsException(
                    'hawk-manage-resource-permissions',
                    previous: $e
                );
            }

            if ($e->getResponse()->getStatusCode() === 409) {
                throw new ResourceAlreadyExistsException($this->builder);
            }

            throw new FailedToWriteResourceException(
                (string)new ErrorResponseMessage($e->getResponse()),
                $this->builder,
                previous: $e
            );
        }

        return $response->getStatusCode() === 201;
    }

    protected function updateExistingResource(ClientInterface $client): bool
    {
        try {
            $response = $client->request(
                'PUT',
                'realms/{realm}/authz/protection/resource_set/' . $this->builder->getId(),
                [
                    'json' => $this->builder->jsonSerialize()
                ]
            );
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                throw new FailedRequestDueToMissingPermissionsException(
                    'hawk-manage-resource-permissions',
                    previous: $e
                );
            }

            throw new FailedToWriteResourceException(
                (string)new ErrorResponseMessage($e->getResponse()),
                $this->builder,
                previous: $e
            );
        }

        return $response->getStatusCode() === 204;
    }
}
