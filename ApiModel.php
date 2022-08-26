<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Utils;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extend this model to use Eloquent with API-requests
 */
class ApiModel extends Model
{
    /**
     * While fetching from remote server, we need to set all attributes of model
     *
     * @var bool
     */
    protected static $unguarded = true;
    public $incrementing = false;

    public function newEloquentBuilder($query): ApiModelEloquentBuilder
    {
        return new ApiModelEloquentBuilder($query);
    }

    public function newBaseQueryBuilder(): Builder
    {
        return new ApiModelBaseQueryBuilder($this->getConnection());
    }

    public function qualifyColumn($column): string
    {
        return $column; //Otherwise here would be <table name>.id
    }

    /**
     * Форматирование даты
     *
     * Переопределил для работы с датой, необзодимо в builder передать
     * Grammar и Processor для автоматического форматирования
     */
    public function getDateFormat() //TODO
    {
    }

    /**
     * Срабатывает перед методами update & delete
     *
     * @param $id
     * @param $columns
     * @return ApiModel
     */
    public function findOrFail($id, $columns = ['*']): ApiModel
    {
        return $this->getModel()->fill([$id]);
    }

    /**
     * Обновление записи
     *
     * findOrFail вызывается перед  update, мы метод переопределили и закинули в модель id
     * поэтому $this->getAttributes[0] - не может быть без id
     *
     * @param array $attributes
     * @param array $options
     * @return ApiModel
     */
    public function update(array $attributes = [], array $options = []): ApiModel
    {
        $connection = $this->getConnection();

        try {
            $response = $connection->getClient()->request('PUT', $this->getTable() . '/' . $this->getIdBeforeSave(), [
                'json' => $attributes
            ]);

            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);

            return $this->fill($decoded['data']);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Получение primary key
     *
     * Перед вызовом этой функции в findOrFail в модель определили primary key
     *
     * @return mixed
     */
    public function getIdBeforeSave(): mixed
    {
        return $this->getAttributes()[0];
    }

    /**
     * Удаление записи
     *
     * findOrFail вызывается перед delete, мы метод переопределили и закинули в модель id
     * поэтому $this->getAttributes[0] - не может быть без id
     *
     * @return bool
     */
    public function delete(): bool
    {
        try {
            $this->getConnection()->getClient()->request('DELETE', $this->getTable() . '/' . $this->getIdBeforeSave());

            return true;
        } catch (NotFoundHttpException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }
}
