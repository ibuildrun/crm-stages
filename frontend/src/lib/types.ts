import type { StageCode } from './stages';

export interface Company {
  id: number;
  name: string;
  stage_code: StageCode;
  stage_name: string;
  created_at: string;
  updated_at: string;
  created_by: number;
}

export interface CrmEvent {
  id: number;
  company_id: number;
  manager_id: number;
  type: string;
  payload: Record<string, unknown>;
  created_at: string;
}

export interface CompanyCard {
  company: Company;
  stage_info: {
    code: string;
    mls_code: string;
    name: string;
    instruction: string;
    exit_conditions: string[];
    restrictions: string[];
  };
  available_actions: string[];
  instruction: string;
  events: CrmEvent[];
}

export interface TransitionResult {
  success: boolean;
  new_stage: string | null;
  errors: string[];
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  errors?: Array<{ code: string; message: string }>;
}
