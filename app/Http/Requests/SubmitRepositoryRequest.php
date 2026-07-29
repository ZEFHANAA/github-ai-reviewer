<?php

namespace App\Http\Requests;

use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class SubmitRepositoryRequest extends FormRequest
{
    private ?GitHubRepositoryUrl $repository = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'repository_url' => ['bail', 'required', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('repository_url')) {
                    return;
                }

                try {
                    $this->repository = GitHubRepositoryUrl::parse($this->string('repository_url')->toString());
                } catch (InvalidArgumentException $exception) {
                    $validator->errors()->add('repository_url', $exception->getMessage());
                }
            },
        ];
    }

    public function repository(): GitHubRepositoryUrl
    {
        return $this->repository ?? throw new \LogicException('The repository URL has not been validated.');
    }
}
