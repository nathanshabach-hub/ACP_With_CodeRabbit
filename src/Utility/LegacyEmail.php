<?php
declare(strict_types=1);

namespace App\Utility;

class LegacyEmail
{
	/**
	 * @var object
	 */
	private $mailer;

	/**
	 * @var string|null
	 */
	private $template;

	/**
	 * @var string|null
	 */
	private $layout;

	/**
	 * @var array
	 */
	private $viewVars = [];

	/**
	 * @var string|null
	 */
	private $emailFormat;

	/**
	 * @var mixed
	 */
	private $to;

	/**
	 * @var mixed
	 */
	private $cc;

	/**
	 * @var mixed
	 */
	private $bcc;

	/**
	 * @var mixed
	 */
	private $from;

	/**
	 * @var string|null
	 */
	private $subject;

	public function __construct(...$args)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$className = '\\Cake\\Mailer\\Email';
			$this->mailer = new $className(...$args);
			return;
		}

		$className = '\\Cake\\Mailer\\Mailer';
		$this->mailer = new $className(...$args);
	}

	public function viewBuilder()
	{
		if (method_exists($this->mailer, 'viewBuilder') && !class_exists('\\Cake\\Mailer\\Email')) {
			return new LegacyEmailViewBuilderProxy($this);
		}

		return $this->mailer->viewBuilder();
	}

	public function setTemplate(string $template)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setTemplate($template);
			return $this;
		}

		$this->template = $template;
		return $this;
	}

	public function setLayout(string $layout)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setLayout($layout);
			return $this;
		}

		$this->layout = $layout;
		return $this;
	}

	public function setViewVars(array $vars)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setViewVars($vars);
			return $this;
		}

		$this->viewVars = $vars;
		return $this;
	}

	public function setEmailFormat(string $format)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setEmailFormat($format);
			return $this;
		}

		$this->emailFormat = $format;
		return $this;
	}

	public function setTo($to)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setTo($to);
			return $this;
		}

		$this->to = $to;
		return $this;
	}

	public function setCc($cc)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setCc($cc);
			return $this;
		}

		$this->cc = $cc;
		return $this;
	}

	public function setBcc($bcc)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setBcc($bcc);
			return $this;
		}

		$this->bcc = $bcc;
		return $this;
	}

	public function setFrom($from)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setFrom($from);
			return $this;
		}

		$this->from = $from;
		return $this;
	}

	public function setSubject(string $subject)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			$this->mailer->setSubject($subject);
			return $this;
		}

		$this->subject = $subject;
		return $this;
	}

	public function send(?string $content = null)
	{
		if (class_exists('\\Cake\\Mailer\\Email')) {
			return $this->mailer->send($content);
		}

		$viewBuilder = $this->mailer->viewBuilder();
		if ($this->template !== null) {
			$viewBuilder->setTemplate($this->template);
		}
		if ($this->layout !== null) {
			$viewBuilder->setLayout($this->layout);
		}
		if (!empty($this->viewVars)) {
			$viewBuilder->setVars($this->viewVars);
		}
		if ($this->emailFormat !== null && method_exists($this->mailer, 'setEmailFormat')) {
			$this->mailer->setEmailFormat($this->emailFormat);
		}
		if ($this->to !== null) {
			$this->mailer->setTo($this->to);
		}
		if ($this->cc !== null) {
			$this->mailer->setCc($this->cc);
		}
		if ($this->bcc !== null) {
			$this->mailer->setBcc($this->bcc);
		}
		if ($this->from !== null) {
			$this->mailer->setFrom($this->from);
		}
		if ($this->subject !== null) {
			$this->mailer->setSubject($this->subject);
		}

		return $content !== null ? $this->mailer->deliver($content) : $this->mailer->deliver();
	}

	public function __call(string $method, array $args)
	{
		$result = $this->mailer->{$method}(...$args);
		if ($result === $this->mailer) {
			return $this;
		}

		return $result;
	}
}

class LegacyEmailViewBuilderProxy
{
	/**
	 * @var \App\Utility\LegacyEmail
	 */
	private $legacyEmail;

	public function __construct(LegacyEmail $legacyEmail)
	{
		$this->legacyEmail = $legacyEmail;
	}

	public function setTemplate(string $template)
	{
		$this->legacyEmail->setTemplate($template);
		return $this;
	}

	public function setLayout(string $layout)
	{
		$this->legacyEmail->setLayout($layout);
		return $this;
	}

	public function setVars(array $vars)
	{
		$this->legacyEmail->setViewVars($vars);
		return $this;
	}
}
