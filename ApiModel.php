<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Utils;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use InvalidArgumentException;
use nikserg\LaravelApiModel\Exception\NotImplemented;
use Symfony\Component\HttpFoundation\Response;
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
    public $incrementing        = false;

    public function newEloquentBuilder($query): ApiModelEloquentBuilder
    {
        return new ApiModelEloquentBuilder($query);
    }

    public function newBaseQueryBuilder(): Builder
    {
        return new ApiModelBaseQueryBuilder(
            $this->getConnection(),
            $this->customUrl
        );
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
     */
    public function update(array $attributes = [], array $options = []): ApiModel
    {
        try {
            $response = $this->getConnection()->getClient()->request('PUT', $this->getUpdateUrl(); . '/' . $this->getIdBeforeSave(), [
                'form_params' => $attributes
            ]);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $body = $response->getBody()->getContents();
        $decoded = Utils::jsonDecode($body, true);

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $this->fill($decoded['data']);
    }

    /**
     * Получение пользовательского url для модели
     */
    protected function getUpdateUrl(): string
    { 
        return $this->customUrl ?? $this->getTable(); 
    }

    /**
     * Получение primary key
     *
     * Перед вызовом этой функции в findOrFail в модель определили primary key
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
     */
    public function delete(): ?bool
    {
        try {
            $this->getConnection()->getClient()->request('DELETE', $this->getTable() . '/' . $this->getIdBeforeSave());

            return true;

        } catch (NotFoundHttpException $e) {

            throw new NotFoundHttpException($e->getMessage());
        }
    }
}
