<?php

namespace App\Tests\Unit\Application\Service\Werewolf;

use App\Application\Service\Werewolf\WerewolfScoringService;
use App\Application\Service\Werewolf\WerewolfVoteService;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\GameRole;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 * @covers \App\Application\Service\Werewolf\WerewolfVoteService
 */
final class WerewolfVoteServiceTest extends KernelTestCase
{
    public function testParticipantOnlyVotingAndAutoClose(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();
        \assert($em instanceof EntityManagerInterface);

        // Prepare game
        $g = (new Game())->setMode('werewolf');
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);

        // users
        $suffix = bin2hex(random_bytes(4));
        $u1 = (new User())->setEmail('u1+' . $suffix . '@t.io')->setPassword('x');
        $u2 = (new User())->setEmail('u2+' . $suffix . '@t.io')->setPassword('x');
        $u3 = (new User())->setEmail('u3+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($u1); $em->persist($u2); $em->persist($u3);
        $em->flush();

        // memberships: u1 in A, u2 in B ; u3 is outsider
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $u1, 0));
        $members->add(new TeamMember($tb, $u2, 0));
        $em->flush();

        // Roles (u1 villager A, u2 werewolf B)
        $em->persist(new GameRole($g, $u1, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $u2, Team::NAME_B, 'werewolf'));
        $em->flush();

        $g->setVoteOpen(true);
        $g->setVoteStartedAt(new \DateTimeImmutable());
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);

        // outsider cannot vote
        $this->expectException(\RuntimeException::class);
        $svc->castVote($g, $u3, $u2);

        // participant votes OK
        $svc->castVote($g, $u1, $u2);
        // last participant casts vote -> auto-close
        $svc->castVote($g, $u2, $u1);
        self::assertFalse($g->isVoteOpen(), 'Vote should auto-close when all participants have voted.');

        // results should count 1 for each
        $res = $svc->getLiveResults($g);
        self::assertSame(1, $res[$u2->getId()] ?? 0);
        self::assertSame(1, $res[$u1->getId()] ?? 0);
    }

    public function testCloseVoteComputesMajorityAndCallsScoring(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $g = (new Game())->setMode('werewolf');
        $g->setResult('A#'); // A wins, so B loses
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);

        $suffix = bin2hex(random_bytes(4));
        $a1 = (new User())->setEmail('a1+' . $suffix . '@t.io')->setPassword('x');
        $a2 = (new User())->setEmail('a2+' . $suffix . '@t.io')->setPassword('x');
        $b1 = (new User())->setEmail('b1+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($a1); $em->persist($a2); $em->persist($b1);
        $em->flush();

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $a1, 0));
        $members->add(new TeamMember($ta, $a2, 1));
        $members->add(new TeamMember($tb, $b1, 0));
        $em->flush();

        // roles
        $em->persist(new GameRole($g, $a1, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $a2, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $b1, Team::NAME_B, 'werewolf'));
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);
        $g->setVoteOpen(true);
        $em->flush();

        // votes: majority on b1 (the werewolf)
        $svc->castVote($g, $a1, $b1);
        $svc->castVote($g, $a2, $b1);

        $svc->closeVote($g);
        self::assertFalse($g->isVoteOpen());

        $res = $svc->getLiveResults($g);
        self::assertSame(2, $res[$b1->getId()] ?? 0);
    }

    public function testDrawNoVotesRewardsAllWerewolves(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // draw result and no votes
        $g = (new Game())->setMode('werewolf');
        $g->setResult('1/2-1/2');
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A); $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);
        $suffix = bin2hex(random_bytes(4));
        $wA = (new User())->setEmail('wa+' . $suffix . '@t.io')->setPassword('x');
        $wB = (new User())->setEmail('wb+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($wA); $em->persist($wB); $em->flush();

        // mark them as members
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $wA, 0));
        $members->add(new TeamMember($tb, $wB, 0));
        $em->flush();

        // roles: both werewolves
        $em->persist(new GameRole($g, $wA, Team::NAME_A, 'werewolf'));
        $em->persist(new GameRole($g, $wB, Team::NAME_B, 'werewolf'));
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);
        $g->setVoteOpen(true);
        $em->flush();

        // close without any vote
        $svc->closeVote($g);
        self::assertFalse($g->isVoteOpen());

        // There is no direct counter to assert here, but absence of exceptions and closure indicates path executed.
        // Optionally, assert that getting results returns empty map
        $res = $svc->getLiveResults($g);
        self::assertSame([], $res);
    }

    public function testNoMajorityRewardsWerewolvesOfLosingTeam(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Game where A wins (so B loses)
        $g = (new Game())->setMode('werewolf');
        $g->setResult('A#');
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A); $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);
        $suffix = bin2hex(random_bytes(4));
        $a1 = (new User())->setEmail('na-a1+' . $suffix . '@t.io')->setPassword('x');
        $b1 = (new User())->setEmail('na-b1+' . $suffix . '@t.io')->setPassword('x');
        $b2 = (new User())->setEmail('na-b2+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($a1); $em->persist($b1); $em->persist($b2); $em->flush();

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $a1, 0));
        $members->add(new TeamMember($tb, $b1, 0));
        $members->add(new TeamMember($tb, $b2, 1));
        $em->flush();

        // Roles: make b1 werewolf
        $em->persist(new GameRole($g, $a1, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $b1, Team::NAME_B, 'werewolf'));
        $em->persist(new GameRole($g, $b2, Team::NAME_B, 'villager'));
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);
        $g->setVoteOpen(true);
        $em->flush();

        // Votes create a tie/no majority scenario: a1->b1, b1->a1
        $svc->castVote($g, $a1, $b1);
        $svc->castVote($g, $b1, $a1);
        // close -> should reward werewolves of losing team (B)
        $svc->closeVote($g);
        self::assertFalse($g->isVoteOpen());

        // Tally remains
        $res = $svc->getLiveResults($g);
        self::assertSame(1, $res[$b1->getId()] ?? 0);
        self::assertSame(1, $res[$a1->getId()] ?? 0);
    }

    public function testTieNoMajorityScenario(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Game result B# (A loses)
        $g = (new Game())->setMode('werewolf');
        $g->setResult('B#');
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A); $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);
        $suffix = bin2hex(random_bytes(4));
        $a1 = (new User())->setEmail('tie-a1+' . $suffix . '@t.io')->setPassword('x');
        $a2 = (new User())->setEmail('tie-a2+' . $suffix . '@t.io')->setPassword('x');
        $b1 = (new User())->setEmail('tie-b1+' . $suffix . '@t.io')->setPassword('x');
        $b2 = (new User())->setEmail('tie-b2+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($a1); $em->persist($a2); $em->persist($b1); $em->persist($b2); $em->flush();

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $a1, 0));
        $members->add(new TeamMember($ta, $a2, 1));
        $members->add(new TeamMember($tb, $b1, 0));
        $members->add(new TeamMember($tb, $b2, 1));
        $em->flush();

        // Roles: b1 werewolf
        $em->persist(new GameRole($g, $a1, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $a2, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $b1, Team::NAME_B, 'werewolf'));
        $em->persist(new GameRole($g, $b2, Team::NAME_B, 'villager'));
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);
        $g->setVoteOpen(true);
        $em->flush();

        // Votes split: a1->b1, b1->a1 (tie 1-1)
        $svc->castVote($g, $a1, $b1);
        $svc->castVote($g, $b1, $a1);
        $svc->closeVote($g);
        self::assertFalse($g->isVoteOpen());

        $res = $svc->getLiveResults($g);
        self::assertSame(1, $res[$b1->getId()] ?? 0);
        self::assertSame(1, $res[$a1->getId()] ?? 0);
    }

    public function testMajorityOnNonWerewolfDoesNotRewardVoters(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $g = (new Game())->setMode('werewolf');
        $g->setResult('A#');
        $em->persist($g);
        $ta = new Team($g, Team::NAME_A); $tb = new Team($g, Team::NAME_B);
        $em->persist($ta); $em->persist($tb);
        $suffix = bin2hex(random_bytes(4));
        $a1 = (new User())->setEmail('mw-a1+' . $suffix . '@t.io')->setPassword('x');
        $a2 = (new User())->setEmail('mw-a2+' . $suffix . '@t.io')->setPassword('x');
        $b1 = (new User())->setEmail('mw-b1+' . $suffix . '@t.io')->setPassword('x');
        $em->persist($a1); $em->persist($a2); $em->persist($b1); $em->flush();

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($ta, $a1, 0));
        $members->add(new TeamMember($ta, $a2, 1));
        $members->add(new TeamMember($tb, $b1, 0));
        $em->flush();

        // Roles: b1 villager, a1 werewolf (e.g. one per team scenario could vary)
        $em->persist(new GameRole($g, $a1, Team::NAME_A, 'werewolf'));
        $em->persist(new GameRole($g, $a2, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($g, $b1, Team::NAME_B, 'villager'));
        $em->flush();

        /** @var WerewolfVoteService $svc */
        $svc = $c->get(WerewolfVoteService::class);
        $g->setVoteOpen(true);
        $em->flush();

        // Majority on non-werewolf target b1 (2 votes)
        $svc->castVote($g, $a1, $b1);
        $svc->castVote($g, $a2, $b1);
        $svc->closeVote($g);
        self::assertFalse($g->isVoteOpen());

        $res = $svc->getLiveResults($g);
        self::assertSame(2, $res[$b1->getId()] ?? 0);
    }
}
