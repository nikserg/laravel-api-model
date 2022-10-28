<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Utils;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use nikserg\LaravelApiModel\Exception\NotImplemented;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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

    /**
     * @param $query
     * @return ApiModelEloquentBuilder
     */
    public function newEloquentBuilder($query): ApiModelEloquentBuilder
    {
        return new ApiModelEloquentBuilder($query);
    }

    /**
     * @return Builder
     */
    public function newBaseQueryBuilder(): Builder
    {
        return new ApiModelBaseQueryBuilder(
            $this->getConnection(),
            $this->getCustomUrl()
        );
    }

    /**
     * @param $column
     * @return string
     */
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
     * @return ApiModel
     */
    public function findOrFail($id, $columns = ['*']): ApiModel
    {
        return $this->getModel();
    }

    /**
     * Обновление записи
     *
     * @return ApiModel
     */
    public function update(array $attributes = [], array $options = []): ApiModel
    {
        try {
            $response = $this->getConnection()->getClient()->request('PUT',
            $this->getCustomUrl() . '/' . $this->getCurrentId(),
            [
                'json' => $attributes
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
     *
     * @return string
     */
    protected function getCustomUrl(): string
    {
        return $this->getTable();
    }

    /**
     * Получение текущего идентификатора из реквеста
     *
     * @return mixed
     */
    public function getCurrentId(): mixed
    {
        return request()->route('id');
    }

    /**
     * Удаление записи
     *
     * @return ?bool
     */
    public function delete(): ?bool
    {
        try {
            $this->getConnection()->getClient()->request('DELETE', $this->getCustomUrl() . '/' . $this->getCurrentId());

            return true;
        } catch (Throwable $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }
}
