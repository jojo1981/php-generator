<?php

declare(strict_types=1);

use Nette\PhpGenerator\PhpNamespace;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// global namespace
$namespace = new PhpNamespace('');

Assert::same('', $namespace->getName());
Assert::same('A', $namespace->simplifyName('A'));
Assert::same('foo\A', $namespace->simplifyName('foo\A'));

$namespace->addUse('Bar\C');

Assert::same('Bar', $namespace->simplifyName('Bar'));
Assert::same('C', $namespace->simplifyName('bar\C'));
Assert::same('C\D', $namespace->simplifyName('Bar\C\D'));

foreach (['String', 'string', 'int', 'float', 'bool', 'array', 'callable', 'self', 'parent', ''] as $type) {
	Assert::same($type, $namespace->simplifyName($type));
}

$namespace->addUseFunction('Foo\a');

Assert::same('bar\c', $namespace->simplifyName('bar\c', $namespace::NAME_FUNCTION));
Assert::same('a', $namespace->simplifyName('foo\A', $namespace::NAME_FUNCTION));
Assert::same('foo\a\b', $namespace->simplifyName('foo\a\b', $namespace::NAME_FUNCTION));

foreach (['String', 'string', 'int', 'float', 'bool', 'array', 'callable', 'self', 'parent', ''] as $type) {
	Assert::same($type, $namespace->simplifyName($type, $namespace::NAME_FUNCTION));
}

$namespace->addUseFunction('Bar\c');

Assert::same('Bar', $namespace->simplifyName('Bar', $namespace::NAME_FUNCTION));
Assert::same('c', $namespace->simplifyName('bar\c', $namespace::NAME_FUNCTION));
Assert::same('C\d', $namespace->simplifyName('Bar\C\d', $namespace::NAME_FUNCTION));

$namespace->addUseConstant('Bar\c');

Assert::same('Bar', $namespace->simplifyName('Bar', $namespace::NAME_CONSTANT));
Assert::same('c', $namespace->simplifyName('bar\c', $namespace::NAME_CONSTANT));
Assert::same('C\d', $namespace->simplifyName('Bar\C\d', $namespace::NAME_CONSTANT));



// namespace
$namespace = new PhpNamespace('Foo');

Assert::same('Foo', $namespace->getName());
Assert::same('\A', $namespace->simplifyName('\A'));
Assert::same('\A', $namespace->simplifyName('A'));
Assert::same('A', $namespace->simplifyName('foo\A'));

Assert::same('A', $namespace->simplifyType('foo\A'));
Assert::same('null|A', $namespace->simplifyType('null|foo\A'));
Assert::same('?A', $namespace->simplifyType('?foo\A'));
Assert::same('A&\Countable', $namespace->simplifyType('foo\A&Countable'));
Assert::same('', $namespace->simplifyType(''));

$namespace->addUse('Foo');
Assert::same('B', $namespace->simplifyName('Foo\B'));

$namespace->addUse('Bar\C');
Assert::same('Foo\C', $namespace->simplifyName('Foo\C'));

Assert::same('\Bar', $namespace->simplifyName('Bar'));
Assert::same('C', $namespace->simplifyName('\bar\C'));
Assert::same('C', $namespace->simplifyName('bar\C'));
Assert::same('C\D', $namespace->simplifyName('Bar\C\D'));
Assert::same('A<C, C\D>', $namespace->simplifyType('foo\A<\bar\C, Bar\C\D>'));
Assert::same('žluťoučký', $namespace->simplifyType('foo\žluťoučký'));

$namespace->addUseFunction('Foo\a');

Assert::same('\bar\c', $namespace->simplifyName('bar\c', $namespace::NAME_FUNCTION));
Assert::same('a', $namespace->simplifyName('foo\A', $namespace::NAME_FUNCTION));
Assert::same('Foo\C\b', $namespace->simplifyName('foo\C\b', $namespace::NAME_FUNCTION));
Assert::same('a\b', $namespace->simplifyName('foo\a\b', $namespace::NAME_FUNCTION));

$namespace->addUseFunction('Bar\c');

Assert::same('\Bar', $namespace->simplifyName('Bar', $namespace::NAME_FUNCTION));
Assert::same('c', $namespace->simplifyName('bar\c', $namespace::NAME_FUNCTION));
Assert::same('C\d', $namespace->simplifyName('Bar\C\d', $namespace::NAME_FUNCTION));


// duplicity
$namespace = new PhpNamespace('Foo');
$namespace->addUse('Bar\C');

Assert::exception(function () use ($namespace) {
	$namespace->addTrait('C');
}, Nette\InvalidStateException::class, "Name 'C' used already as alias for Bar\\C.");

Assert::exception(function () use ($namespace) {
	$namespace->addTrait('c');
}, Nette\InvalidStateException::class, "Name 'c' used already as alias for Bar\\C.");

$namespace->addClass('B');
Assert::exception(function () use ($namespace) {
	$namespace->addUse('Lorem\B', 'B');
}, Nette\InvalidStateException::class, "Name 'B' used already for 'Foo\\B'.");

Assert::exception(function () use ($namespace) {
	$namespace->addUse('lorem\b', 'b');
}, Nette\InvalidStateException::class, "Name 'b' used already for 'Foo\\B'.");

$namespace->addUseFunction('Bar\f1');
Assert::exception(function () use ($namespace) {
	$namespace->addFunction('f1');
}, Nette\InvalidStateException::class, "Name 'f1' used already as alias for Bar\\f1.");

Assert::exception(function () use ($namespace) {
	$namespace->addFunction('F1');
}, Nette\InvalidStateException::class, "Name 'F1' used already as alias for Bar\\f1.");

$namespace->addFunction('f2');
Assert::exception(function () use ($namespace) {
	$namespace->addUseFunction('Bar\f2', 'f2');
}, Nette\InvalidStateException::class, "Name 'f2' used already for 'Foo\\f2'.");

Assert::exception(function () use ($namespace) {
	$namespace->addUseFunction('Bar\f2', 'F2');
}, Nette\InvalidStateException::class, "Name 'F2' used already for 'Foo\\f2'.");

Assert::same(['C' => 'Bar\C'], $namespace->getUses());
Assert::same(['f1' => 'Bar\f1'], $namespace->getUses($namespace::NAME_FUNCTION));


// alias generation
$namespace = new PhpNamespace('');
$namespace->addUse('C');
Assert::same('C', $namespace->simplifyName('C'));
$namespace->addUse('Bar\C');
Assert::same('C1', $namespace->simplifyName('Bar\C'));

$namespace = new PhpNamespace('');
$namespace->addUse('Bar\C');
$namespace->addUse('C');
Assert::same('C1', $namespace->simplifyName('C'));

$namespace = new PhpNamespace('');
$namespace->addUse('A');
Assert::same('A', $namespace->simplifyName('A'));
$namespace->addUse('Bar\A');
Assert::same('A1', $namespace->simplifyName('Bar\A'));

$namespace = new PhpNamespace('Foo');
$namespace->addUse('C');
Assert::same('C', $namespace->simplifyName('C'));
$namespace->addUse('Bar\C');
Assert::same('C1', $namespace->simplifyName('Bar\C'));
Assert::same('\Foo\C', $namespace->simplifyName('Foo\C'));
$namespace->addUse('Foo\C');
Assert::same('C2', $namespace->simplifyName('Foo\C'));

$namespace = new PhpNamespace('Foo');
$namespace->addUse('Bar\C');
$namespace->addUse('C');
Assert::same('C1', $namespace->simplifyName('C'));

$namespace = new PhpNamespace('Foo\Bar');
$namespace->addUse('Foo\Bar\Baz\Qux');
Assert::same('Qux', $namespace->simplifyName('Foo\Bar\Baz\Qux'));

$namespace = new PhpNamespace('Foo');
$namespace->addUseFunction('Bar\c');
$namespace->addUseFunction('c');
Assert::same('c1', $namespace->simplifyName('c', $namespace::NAME_FUNCTION));
