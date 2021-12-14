<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

use RC\Domain\UserInterest\InterestId\Pure\Single\InterestId;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking as NetworkingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\SkySurfing as SkySurfingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea as SpecificAreaId;
use RC\Domain\UserInterest\InterestId\Pure\Single\DayDreaming as DayDreamingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\ImpactAnalysisAndRiskAssessment as ImpactAnalysisAndRiskAssessmentId;
use RC\Domain\UserInterest\InterestId\Pure\Single\InterviewPreparation as InterviewPreparationId;
use RC\Domain\UserInterest\InterestId\Pure\Single\CareerBuilding as CareerBuildingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\ProductDiscovery as ProductDiscoveryId;
use RC\Domain\UserInterest\InterestId\Pure\Single\TeamMotivation as TeamMotivationId;
use RC\Domain\UserInterest\InterestId\Pure\Single\MetricsImprovement as MetricsImprovementId;
use RC\Domain\UserInterest\InterestId\Pure\Single\ProductCultureBuilding as ProductCultureBuildingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\Hiring as HiringId;
use RC\Domain\UserInterest\InterestId\Pure\Single\CareerLevelUp as CareerLevelUpId;
use RC\Domain\UserInterest\InterestId\Pure\Single\CasesDiscussion as CasesDiscussionId;
use RC\Domain\UserInterest\InterestId\Pure\Single\TeamManagement as TeamManagementId;
use RC\Domain\UserInterest\InterestId\Pure\Single\UnitEconomics as UnitEconomicsId;
use RC\Domain\UserInterest\InterestId\Pure\Single\ClientSegmentationAndMarketAnalysis as ClientSegmentationAndMarketAnalysisId;
use RC\Domain\UserInterest\InterestId\Pure\Single\Strategy as StrategyId;
use RC\Domain\UserInterest\InterestId\Pure\Single\TaskPrioritization as TaskPrioritizationId;
use RC\Domain\UserInterest\InterestId\Pure\Single\MVPBuilding as MVPBuildingId;
use RC\Domain\UserInterest\InterestId\Pure\Single\AllThingsDevelopment as AllThingsDevelopmentId;
use RC\Domain\UserInterest\InterestId\Pure\Single\UX as UXId;
use RC\Domain\UserInterest\InterestId\Pure\Single\UserAcquisition as UserAcquisitionId;
use RC\Domain\UserInterest\InterestId\Pure\Single\Sales as SalesId;
use RC\Domain\UserInterest\InterestId\Pure\Single\BusinessModelsAndMonetization as BusinessModelsAndMonetizationId;
use RC\Domain\UserInterest\InterestId\Pure\Single\InvestmentAttraction as InvestmentAttractionId;

class FromInterestId extends InterestName
{
    private $concrete;

    public function __construct(InterestId $interestId)
    {
        $this->concrete = $this->all()[$interestId->value()] ?? new NonExistent();
    }

    public function value(): string
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
            (new NetworkingId())->value() => new Networking(),
            (new SpecificAreaId())->value() => new SpecificArea(),
            (new SkySurfingId())->value() => new SkySurfing(),
            (new DayDreamingId())->value() => new DayDreaming(),
            (new ImpactAnalysisAndRiskAssessmentId())->value() => new ImpactAnalysisAndRiskAssessment(),
            (new InterviewPreparationId())->value() => new InterviewPreparation(),
            (new CareerBuildingId())->value() => new CareerBuilding(),
            (new ProductDiscoveryId())->value() => new ProductDiscovery(),
            (new TeamMotivationId())->value() => new TeamMotivation(),
            (new MetricsImprovementId())->value() => new MetricsImprovement(),
            (new ProductCultureBuildingId())->value() => new ProductCultureBuilding(),
            (new HiringId())->value() => new Hiring(),
            (new CareerLevelUpId())->value() => new CareerLevelUp(),
            (new CasesDiscussionId())->value() => new CasesDiscussion(),
            (new TeamManagementId())->value() => new TeamManagement(),
            (new UnitEconomicsId())->value() => new UnitEconomics(),
            (new ClientSegmentationAndMarketAnalysisId())->value() => new ClientSegmentationAndMarketAnalysis(),
            (new StrategyId())->value() => new Strategy(),
            (new TaskPrioritizationId())->value() => new TaskPrioritization(),
            (new MVPBuildingId())->value() => new MVPBuilding(),
            (new AllThingsDevelopmentId())->value() => new AllThingsDevelopment(),
            (new UXId())->value() => new UX(),
            (new UserAcquisitionId())->value() => new UserAcquisition(),
            (new SalesId())->value() => new Sales(),
            (new BusinessModelsAndMonetizationId())->value() => new BusinessModelsAndMonetization(),
            (new InvestmentAttractionId())->value() => new InvestmentAttraction(),
        ];
    }
}