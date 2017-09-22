<?php 
namespace App\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Class Validator
 * @package App\Validation
 */
class Validator
{
	protected $errors;

    /**
     * @param $request
     * @param array $rules
     * @return $this
     */
	public function validate($request,array $rules)
	{
		foreach ($rules as $field => $rule) {
			try {
				$rule->setName(ucfirst($field))->assert($request->getParam($field));
			} catch (NestedValidationException $e) {
				$this->errors[$field] = $e->getMessages();
			}
			
		}

		$_SESSION['errors'] = $this->errors;
		return $this;
	}

    /**
     * @return bool
     */
	public function failed()
	{
		return !empty($this->errors);
	}
}