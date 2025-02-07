<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserBundle\Tests\Command;

use Nucleos\UserBundle\Command\PromoteUserCommand;
use Nucleos\UserBundle\Util\UserManipulator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

final class PromoteUserCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $commandTester = $this->createCommandTester($this->getManipulator('user', 'role', false));
        $exitCode      = $commandTester->execute([
            'username' => 'user',
            'role'     => 'role',
        ], [
            'decorated'   => false,
            'interactive' => false,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Role "role" has been added to user "user"/', $commandTester->getDisplay());
    }

    public function testExecuteInteractiveWithQuestionHelper(): void
    {
        $application = new Application();

        $helper = $this->createMock(QuestionHelper::class);

        $helper->expects(self::exactly(2))
            ->method('ask')
            ->willReturn(
                'user',
                'role',
            )
        ;

        $application->getHelperSet()->set($helper, 'question');

        $commandTester = $this->createCommandTester($this->getManipulator('user', 'role', false), $application);
        $exitCode      = $commandTester->execute([], [
            'decorated'   => false,
            'interactive' => true,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Role "role" has been added to user "user"/', $commandTester->getDisplay());
    }

    private function createCommandTester(UserManipulator $manipulator, ?Application $application = null): CommandTester
    {
        if (null === $application) {
            $application = new Application();
        }

        $application->setAutoExit(false);

        $command = new PromoteUserCommand($manipulator);

        $application->add($command);

        return new CommandTester($application->find('nucleos:user:promote'));
    }

    /**
     * @return MockObject&UserManipulator
     */
    private function getManipulator(string $username, string $role, bool $super): MockObject
    {
        $manipulator = $this->createMock(UserManipulator::class);
        if ($super) {
            $manipulator
                ->expects(self::once())
                ->method('promote')
                ->with($username)
                ->willReturn(true)
            ;
        } else {
            $manipulator
                ->expects(self::once())
                ->method('addRole')
                ->with($username, $role)
                ->willReturn(true)
            ;
        }

        return $manipulator;
    }
}
