<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class FromString extends InterestName
{
    private $concrete;

    public function __construct(string $interestName)
    {
        $this->concrete = $this->all()[$interestName] ?? new NonExistent();
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
            (new Networking())->value() => new Networking(),
            (new SpecificArea())->value() => new SpecificArea(),
            (new SkySurfing())->value() => new SkySurfing(),
            (new DayDreaming())->value() => new DayDreaming(),
            (new ImpactAnalysisAndRiskAssessment())->value() => new ImpactAnalysisAndRiskAssessment(),
            (new InterviewPreparation())->value() => new InterviewPreparation(),
            (new CareerBuilding())->value() => new CareerBuilding(),
            (new ProductDiscovery())->value() => new ProductDiscovery(),
            (new TeamMotivation())->value() => new TeamMotivation(),
            (new MetricsImprovement())->value() => new MetricsImprovement(),
            (new ProductCultureBuilding())->value() => new ProductCultureBuilding(),
            (new Hiring())->value() => new Hiring(),
            (new CareerLevelUp())->value() => new CareerLevelUp(),
            (new CasesDiscussion())->value() => new CasesDiscussion(),
            (new TeamManagement())->value() => new TeamManagement(),
            (new UnitEconomics())->value() => new UnitEconomics(),
            (new ClientSegmentationAndMarketAnalysis())->value() => new ClientSegmentationAndMarketAnalysis(),
        ];
    }
}