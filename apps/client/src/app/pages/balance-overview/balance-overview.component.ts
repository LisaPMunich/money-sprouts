import { Component, OnInit } from '@angular/core';
import { User } from '@money-sprouts/shared/domain';
import { Observable } from 'rxjs';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'money-sprouts-balance-overview',
  templateUrl: './balance-overview.component.html',
  styleUrls: ['./balance-overview.component.scss'],
})
export class BalanceOverviewComponent implements OnInit {
  user$: Observable<User | null>;

  constructor(private userService: UserService) {}

  ngOnInit() {
    this.user$ = this.userService.currentUser$;
    
  }

  getFunnyImage(balance: number | undefined): string {
    switch (true) {
      case balance <= 0:
        return './assets/images/3d-empty-box.png';
      case balance < 500:
        return './assets/images/3d-dog-from-behind.png'; 
      case balance < 1000:
        return './assets/images/3d-sitting-dog.png'; 
      case balance < 1500:
        return './assets/images/3d-dog-with-leash.png';
      case balance < 2000:
        return './assets/images/3d-dog-with-bag.png';
      case balance < 5000:
        return './assets/images/3d-man-playing-with-dog.png';
      default:
        return './assets/images/3d-dog-and-boy-jumping.png';
    }
  }
}