<?php
namespace App\Model\Table;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class EventsubmissionsTable extends Table{
    
    public function initialize(array $config): void
    {
		$this->belongsTo('Conventionregistrations', [
            'className' => 'Conventionregistrations',
            'foreignKey' => 'conventionregistration_id',
            'propertyName' => 'Conventionregistrations'
        ]);
		
		$this->belongsTo('Conventions', [
            'className' => 'Conventions',
            'foreignKey' => 'convention_id',
            'propertyName' => 'Conventions'
        ]);
		
		$this->belongsTo('Users', [
            'className' => 'Users',
            'foreignKey' => 'user_id',
            'propertyName' => 'Users'
        ]);
		
		$this->belongsTo('Seasons', [
            'className' => 'Seasons',
            'foreignKey' => 'season_id',
            'propertyName' => 'Seasons'
        ]);
		
		$this->belongsTo('Events', [
            'className' => 'Events',
            'foreignKey' => 'event_id',
            'propertyName' => 'Events'
        ]);
		
		$this->belongsTo('Students', [
            'className' => 'Users',
            'foreignKey' => 'student_id',
            'propertyName' => 'Students'
        ]);
		
		$this->belongsTo('Uploadeduser', [
            'className' => 'Users',
            'foreignKey' => 'uploaded_by_user_id',
            'propertyName' => 'Uploadeduser'
        ]);
		
		$this->belongsTo('Judge', [
            'className' => 'Users',
            'foreignKey' => 'guideline_breach_by_judge_id',
            'propertyName' => 'Judge'
        ]);
		
		$this->belongsTo('Judgecommand', [
            'className' => 'Users',
            'foreignKey' => 'mark_command_by_judge_id',
            'propertyName' => 'Judgecommand'
        ]);
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(function ($entity) {
            $conventionRegistrationId = (int)($entity->conventionregistration_id ?? 0);
            $eventId = (int)($entity->event_id ?? 0);
            $studentId = (int)($entity->student_id ?? 0);
            $groupName = trim((string)($entity->group_name ?? ''));

            if ($conventionRegistrationId <= 0 || $eventId <= 0) {
                return true;
            }

            $query = $this->find()->where([
                'Eventsubmissions.conventionregistration_id' => $conventionRegistrationId,
                'Eventsubmissions.event_id' => $eventId,
            ]);

            if ($studentId > 0) {
                $query->where(['Eventsubmissions.student_id' => $studentId]);
            } else {
                if ($groupName === '') {
                    return true;
                }
                $query->where([
                    'Eventsubmissions.student_id' => 0,
                    'Eventsubmissions.group_name' => $groupName,
                ]);
            }

            if (!empty($entity->id)) {
                $query->where(['Eventsubmissions.id !=' => (int)$entity->id]);
            }

            $exists = (bool)$query->first();
            return !$exists;
        }, 'uniqueSubmissionPerEntry', [
            'errorField' => 'event_id',
            'message' => 'A submission already exists for this student/group in the selected event.',
        ]);

        return $rules;
    }
    
     
    
    

}
?>