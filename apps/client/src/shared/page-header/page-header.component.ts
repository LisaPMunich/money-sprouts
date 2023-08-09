import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { UserService } from '../../app/services/user.service';
import { Observable, Subject, debounceTime, distinctUntilChanged, filter, map, takeUntil } from 'rxjs';
import { User } from '@money-sprouts/shared/domain';

@Component({
  selector: 'money-sprouts-page-header',
  templateUrl: './page-header.component.html',
  styleUrls: ['./page-header.component.scss'],
})
export class PageHeaderComponent implements OnInit, OnDestroy {
  childClass: string;
  username: string;
  user$: Observable<User | null>
  urlSegment: string;
  logout = 'Logout';
  avatar: string;
  id: number;
  isLoading = false;

  private destroy$ = new Subject<void>();


  constructor(
    private router: Router,
    public userService: UserService,
    ) {
      this.userService.loading.subscribe(loading => {
        this.isLoading = loading;
      });
    }

  ngOnInit() {
    const urlSegments = this.router.url.split('/');
    this.urlSegment = urlSegments[urlSegments.length - 1];
    this.username = urlSegments[2];

    this.user$ = this.userService.currentUser$.pipe(
      distinctUntilChanged()
    );   

    this.router.events
      .pipe(
        filter(event => event instanceof NavigationEnd),
        map(() => this.router.url.split('/')[2]),
        distinctUntilChanged(),
        takeUntil(this.destroy$)
      )
       .subscribe(username => {
        console.log(console.log('User in header: ', username))
        this.username = username;
        this.userService.getUserByUsername(username)
      });
    
  }

  goBack() {
      if (this.urlSegment === 'dashboard') {
          this.router.navigate(['userselection']);
      } else if (this.urlSegment === 'history' || this.urlSegment === 'plan' || this.urlSegment === 'overview'){
        this.router.navigate([`user/${this.username}/dashboard`]);
      } else {
        return;
      }
  }

  goToUserselection() {
    this.router.navigate(['userselection']);
  }

  get pageTitle(): string {
    const pageName = this.router.url.split('/')[3];
    switch (pageName) {
      case 'dashboard':
        return 'Dashboard';
      case 'overview':
        return 'Overview';
      case 'history':
        return 'History';
      case 'plan':
        return 'Plan';
      default:
        return '';
    }
  }

  onLogout() {
    this.userService.logoutOrDeselectUser();
  }

  ngOnDestroy(): void {
      this.destroy$.next();
      this.destroy$.complete();
  }
}
