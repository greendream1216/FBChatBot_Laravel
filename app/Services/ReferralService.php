<?php namespace App\Services;

use DB;
use App\Models\User;
use App\Repositories\User\UserRepository;

class PageService
{
    /**
     * @type UserRepository
     */

    private $userRepository;
    private $rewardAmount;
    private $unlimitedAmount;


    public function __construct(UserRepository $userRepo)
    {
        $this->userRepository = $userRepo;
        $this->rewardAmount = 5;
        $this->unlimitedAmount = 99999;
    }

    private function distributeAward(User $user)
    {
        if($user->countReferrals() == 1)
        {
            $user->limits = ['msg_templates' => $this->unlimitedAmount, 'keyword_replies' => $user->limits['keyword_replies'], 'sequences' => $user->limits['sequences']];
            return $user->save();
        }
        else if($user->countReferrals() == 2)
        {
            $user->limits = ['msg_templates' => $this->unlimitedAmount, 'keyword_replies' => $this->unlimitedAmount, 'sequences' => $user->limits['sequences']];
            return $user->save();
        }
        else if($user->countReferrals() == 3)
        {
            $user->limits = ['msg_templates' => $this->unlimitedAmount, 'keyword_replies' => $this->unlimitedAmount, 'sequences' => $this->unlimitedAmount];
            return $user->save();
        }
        else if($user->countReferrals() > 3 && $user->countReferrals <= 10)
        {
            return $this->userRepository->addCredits($user, $this->rewardAmount);
        }
        else
        {
            /*
             * ToDo: what do we do if he's above the limit?
             */
            return false;
        }
    }

    public function getIdFromCode($referralCode)
    {
        return explode('::', $this->userRepository->getDecryptedCode($referralCode))[2];
    }

    public function createConnection(User $child, $referralCode)
    {
        $parentId = getIdFromCode($referralCode);
        $parent = User::find($parentId);

        $this->userRepository->connectReferrals($parent, $child);
        $this->distributeAward($parent);
    }
}