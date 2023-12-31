<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Avatar;
use App\Entity\Category;
use App\Factory\AccountFactory;
use App\Factory\AvatarFactory;
use App\Factory\CategoryFactory;
use App\Factory\EarningFactory;
use App\Factory\ExpenseFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Proxy;

class AppFixtures extends Fixture
{
    private Category|Proxy $catSpent;
    private Category|Proxy $catEarn;
    private Proxy|Avatar $avatarMale;
    private Proxy|Avatar $avatarFemale;

    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadAvatars();
        $this->loadCategories();

        if ($_ENV['APP_ENV'] !== 'prod') {
            $this->loadUsers();
        }
        
        $this->loadAdmin($manager);
    }

    public function loadAvatars(): void
    {
        $this->avatarFemale = AvatarFactory::createOne([
            'url' => '/assets/images/avatar_female.png',
        ]);
        $this->avatarMale = AvatarFactory::createOne([
            'url' => '/assets/images/avatar_male.png',
        ]);
    }

    public function loadCategories(): void
    {
        $this->catEarn = CategoryFactory::createOne([
            'name' => 'Pocket Money',
        ]);
        $this->catSpent = CategoryFactory::createOne([
            'name' => 'Expense',
        ]);
    }

    public function loadUsers(): void
    {
        $this->generateAccountWithAvatar($this->avatarMale);
        $this->generateAccountWithAvatar($this->avatarFemale);
    }

    /**
     * @param Proxy<Avatar>|Avatar $avatar
     * @return void
     */
    public function generateAccountWithAvatar(Proxy|Avatar $avatar): void
    {
        $user = UserFactory::createOne();
        $account = AccountFactory::createOne([
            'user' => $user,
            'avatar' => $avatar,
        ]);

        EarningFactory::createMany(4, [
            'applied' => true,
            'account' => $account,
            'category' => $this->catEarn,
            'value' => 3000,
        ]);

        ExpenseFactory::createMany(
            30,
            [
                'account' => $account,
                'category' => $this->catSpent,
            ]
        );
    }

    public function loadAdmin(ObjectManager $manager): void
    {
        $admin = UserFactory::new()
            ->admin($_ENV['ADMIN_PASSWORD'])->create()
            ->setEmail($_ENV['ADMIN_MAIL'])
            ->setName('Admin');

        $manager->persist($admin);
        $manager->flush();
    }
}
