<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

use RC\Domain\UserInterest\InterestName\Pure\DayDreaming as DayDreamingName;
use RC\Domain\UserInterest\InterestName\Pure\InterestName;
use RC\Domain\UserInterest\InterestName\Pure\Networking as NetworkingName;
use RC\Domain\UserInterest\InterestName\Pure\SkySurfing as SkySurfingName;
use RC\Domain\UserInterest\InterestName\Pure\SpecificArea as SpecificAreaName;
use RC\Domain\UserInterest\InterestName\Pure\ImpactAnalysisAndRiskAssessment as ImpactAnalysisAndRiskAssessmentName;
use RC\Domain\UserInterest\InterestName\Pure\InterviewPreparation as InterviewPreparationName;
use RC\Domain\UserInterest\InterestName\Pure\CareerBuilding as CareerBuildingName;
use RC\Domain\UserInterest\InterestName\Pure\ProductDiscovery as ProductDiscoveryName;
use RC\Domain\UserInterest\InterestName\Pure\TeamMotivation as TeamMotivationName;
use RC\Domain\UserInterest\InterestName\Pure\MetricsImprovement as MetricsImprovementName;
use RC\Domain\UserInterest\InterestName\Pure\ProductCultureBuilding as ProductCultureBuildingName;
use RC\Domain\UserInterest\InterestName\Pure\Hiring as HiringName;
use RC\Domain\UserInterest\InterestName\Pure\CareerLevelUp as CareerLevelUpName;
use RC\Domain\UserInterest\InterestName\Pure\CasesDiscussion as CasesDiscussionName;
use RC\Domain\UserInterest\InterestName\Pure\TeamManagement as TeamManagementName;
use RC\Domain\UserInterest\InterestName\Pure\UnitEconomics as UnitEconomicsName;
use RC\Domain\UserInterest\InterestName\Pure\ClientSegmentationAndMarketAnalysis as ClientSegmentationAndMarketAnalysisName;

class FromInterestName extends InterestId
{
    private $concrete;

    public function __construct(InterestName $interestName)
    {
        $this->concrete = $this->all()[$interestName->value()] ?? new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new NetworkingName())->value() => new Networking(),
            (new SpecificAreaName())->value() => new SpecificArea(),
            (new SkySurfingName())->value() => new SkySurfing(),
            (new DayDreamingName())->value() => new DayDreaming(),
            (new ImpactAnalysisAndRiskAssessmentName())->value() => new ImpactAnalysisAndRiskAssessment(),
            (new InterviewPreparationName())->value() => new InterviewPreparation(),
            (new CareerBuildingName())->value() => new CareerBuilding(),
            (new ProductDiscoveryName())->value() => new ProductDiscovery(),
            (new TeamMotivationName())->value() => new TeamMotivation(),
            (new MetricsImprovementName())->value() => new MetricsImprovement(),
            (new ProductCultureBuildingName())->value() => new ProductCultureBuilding(),
            (new HiringName())->value() => new Hiring(),
            (new CareerLevelUpName())->value() => new CareerLevelUp(),
            (new CasesDiscussionName())->value() => new CasesDiscussion(),
            (new TeamManagementName())->value() => new TeamManagement(),
            (new UnitEconomicsName())->value() => new UnitEconomics(),
            (new ClientSegmentationAndMarketAnalysisName())->value() => new ClientSegmentationAndMarketAnalysis(),
        ];
    }
}